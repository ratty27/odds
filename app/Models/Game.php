<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Candidate;
use App\Models\User;
use App\Models\Bet;
use App\Models\Odd;
use App\Odds\RuleWin;
use App\Odds\RuleQuinella;
use App\Odds\RuleExacta;

class Game extends Model
{
	use HasFactory;

    const POLICY_IS_PRIVATE      = 0;
    const POLICY_APPLYING_PUBLIC = 1;
    const POLICY_PUBLIC          = 2;

	/**
	 *	Create new game
	 */
	public static function new_game($user_id, $name, $limit, $comment, $enabled, $candidate_names, $pubset, $exclude_history = false, $candidate_colors = [])
	{
		$game = new Game;
		$game->name = $name;
		$game->limit = $limit;
		$game->comment = $comment;
		if( $game->comment == null )
		{
			$game->comment = '';
		}
		$game->user_id = $user_id;
		$game->next_update = date("Y/m/d H:i:s");
		$game->exclusion_update = 0;
		$game->exclude_history = $exclude_history ? 1 : 0;

		$game->enabled = $enabled;

		if( $pubset == 0 )
		{	// private
			$game->is_public = 0;
		}
		else
		{	// public
			$game->is_public = 1;
		}

		if( $game->save() )
		{
			$records = array();
			$now = date("Y/m/d H:i:s");
			for( $i = 0; $i < count($candidate_names); ++$i )
			{
				$records[] = [
					'name' => $candidate_names[$i],
					'game_id' => $game->id,
					'disp_order' => $i,
					'frame_color' => $candidate_colors[$i] ?? null,
					'created_at' => $now,
					'updated_at' => $now,
				];
			}
			Candidate::insert( $records );

			$game->update_odds();
			return $game->id;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *	Update an existed game
	 */
	public function update_game($name, $limit, $comment, $enabled, $candidate_names, $pubset, $exclude_history = false, $candidate_colors = [])
	{
		$this->name = $name;
		$this->limit = $limit;
		$this->comment = $comment;
		if( $this->comment == null )
		{
			$this->comment = '';
		}
		$this->next_update = date("Y/m/d H:i:s");
		$this->exclusion_update = 0;
		$this->enabled = $enabled;
		$this->exclude_history = $exclude_history ? 1 : 0;

		if( $pubset == 0 )
		{	// private
			$this->is_public = 0;
		}
		else
		{	// public
			if( $this->is_public == 0 )
			{	// Apply to public
				$this->is_public = 1;
			}
		}

		if( $this->update() )
		{
			$candidate_updated = array();

			// Update existing records
			$candidates = Candidate::where('game_id', $this->id)
				->select('id', 'name', 'disp_order', 'frame_color')
				->get();
			foreach( $candidates as &$candidate )
			{
				$index = array_search($candidate->name, $candidate_names);
				if( $index === false )
				{	// Delete a candidate
					$candidate->safe_delete();
				}
				else
				{	// Update an existed candidate
					$new_color = $candidate_colors[$index] ?? null;
					if( $candidate->disp_order != $index || $candidate->frame_color != $new_color )
					{
						$candidate->disp_order = $index;
						$candidate->frame_color = $new_color;
						$candidate->update();
					}
					array_push($candidate_updated, $candidate->name);
				}
			}

			// Add new candidates
			$now = date("Y/m/d H:i:s");
			$records = array();
			for($index = 0; $index < count($candidate_names); ++$index)
			{
				if( !in_array($candidate_names[$index], $candidate_updated) )
				{
					$records[] = [
						'name' => $candidate_names[$index],
						'game_id' => $this->id,
						'disp_order' => $index,
						'frame_color' => $candidate_colors[$index] ?? null,
						'created_at' => $now,
						'updated_at' => $now,
					];
				}
			}
			if( count($records) > 0 )
			{
				Candidate::insert( $records );
			}

			$this->update_odds();
		}
	}

	/**
	 *	Delete this game
	 */
	public function safe_delete()
	{
		Bet::where('game_id', $this->id)->delete();
		Odd::where('game_id', $this->id)->delete();
		Candidate::where('game_id', $this->id)->delete();
		$this->delete();
	}

	/**
	 *	Finish this game
	 */
	public function finish()
	{
		$this->status = 2;
		$this->update_odds();
	}

	/**
	 *  Compare 'odds' element for sorting
	 */
	static function compare_odds($a, $b)
	{
		return $a['odds'] <=> $b['odds'];
	}

	/**
	 *	Check whether a type of odds is enabled
	 */
	public function is_enabled($type)
	{
		return ($this->enabled & (1 << $type)) != 0;
	}

	/**
	 *  Update game's odds
	 */
	public function update_odds()
	{
//		$game_id = $this->id;

		// for win
		$candidates = Candidate::where('game_id', $this->id)->orderBy('id', 'asc')->select('id')->get();
		if( count($candidates) > 0 )
		{
			// Calculate odds for each candidate
			RuleWin::update_odds( $this->id, $candidates, config('odds.dummy_points') );
			if( $this->is_enabled(Bet::TYPE_QUINELLA) )
				RuleQuinella::update_odds( $this->id, $candidates, config('odds.dummy_points') );
			if( $this->is_enabled(Bet::TYPE_EXACTA) )
				RuleExacta::update_odds( $this->id, $candidates, config('odds.dummy_points') );
		}

		$this->visibility = Bet::where('game_id', $this->id)->distinct('user_id')->count();
		$this->next_update = date('Y/m/d H:i:s', strtotime('+' . config('odds.interval_calc_odds')));
		// Mark clean so cron skips until the next bet sets exclusion_update back to 0.
		$this->exclusion_update = 1;
		$this->update();
	}

	/**
	 *  Update game's odds
	 */
	public function update_odds_if_needs()
	{
		if( !config('odds.calc_odds_on_request') )
		{
			return;
		}
		if( $this->status == 0 )
		{
			$game = $this;
			DB::transaction(function () use($game)
				{
					if( $game->exclusion_update == 0 )
					{
						$current = time();
						$next = strtotime($game->next_update);
						//Log::info('Update check: ' . $current . ' / ' . $next );
						if( $current >= $next )
						{
							$game->increment('exclusion_update');
							$game->save();
							if( $game->exclusion_update == 1 )
							{
								$game->update_odds();
							}
						}
					}
				}
			);
		}
	}

	/**
	 *	Get favorite games
	 * 
	 *	@param	$num		# of games to pick
	 */
	public static function get_favorite_games($num)
	{
		return Game::where('status', 0)->where('is_public', 3)->orderBy('visibility', "desc")->take($num)
			->select('id', 'name', 'comment')->get();
	}
}
