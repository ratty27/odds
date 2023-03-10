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

	/**
	 *	Create new game
	 */
	public static function new_game($user_id, $name, $limit, $comment, $enabled, $candidate_names)
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

		$game->enabled = $enabled;
		if( $game->save() )
		{
			$records = array();
			$now = date("Y/m/d H:i:s");
			for( $i = 0; $i < count($candidate_names); ++$i )
			{
				$records[] = [
					'name' => $candidate_names[$i], 'game_id' => $game->id, 'disp_order' => $i,
					'created_at' => $now, 'updated_at' => $now,
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
	public function update_game($name, $limit, $comment, $enabled, $candidate_names)
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
		if( $this->update() )
		{
			$candidate_updated = array();

			// Update existing records
			$candidates = Candidate::where('game_id', $this->id)
				->select('id', 'name', 'disp_order')
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
					if( $candidate->disp_order != $index )
					{
						$candidate->disp_order = $index;
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
						'name' => $candidate_names[$index], 'game_id' => $this->id, 'disp_order' => $index,
						'created_at' => $now, 'updated_at' => $now,
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

		$this->next_update = date('Y/m/d H:i:s', strtotime('+' . config('odds.interval_calc_odds')));
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
}
