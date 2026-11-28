<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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
			$weights = Candidate::calc_dummy_weights($user_id, $candidate_names);
			$records = array();
			$now = date("Y/m/d H:i:s");
			for( $i = 0; $i < count($candidate_names); ++$i )
			{
				$name = $candidate_names[$i];
				$records[] = [
					'name' => $name,
					'game_id' => $game->id,
					'disp_order' => $i,
					'frame_color' => $candidate_colors[$i] ?? null,
					'dummy_weight' => $weights[$name] ?? null,
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

			$this->refresh_dummy_weights();
			$this->update_odds();
		}
	}

	/**
	 *	Recalculate and persist dummy bet weights from past results.
	 *	Called on create/edit only; odds cron reuses the stored values.
	 */
	public function refresh_dummy_weights()
	{
		$candidates = Candidate::where('game_id', $this->id)
			->orderBy('disp_order', 'asc')
			->select('id', 'name', 'dummy_weight')
			->get();
		if( count($candidates) == 0 )
		{
			return;
		}
		$names = [];
		foreach( $candidates as $candidate )
		{
			$names[] = $candidate->name;
		}
		$weights = Candidate::calc_dummy_weights($this->user_id, $names);
		foreach( $candidates as $candidate )
		{
			$candidate->dummy_weight = $weights[$candidate->name] ?? null;
			$candidate->update();
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
	 *	Build per-pattern dummy points from stored candidate weights.
	 *	Win uses weight directly. Quinella/exacta use Harville from win weights.
	 *	Total dummy pool stays base_points * pattern_count (same scale as uniform).
	 *
	 *	@param	\Illuminate\Support\Collection	$candidates	id + dummy_weight, ordered by id
	 *	@param	int								$type		Bet::TYPE_*
	 *	@param	int|float						$base_points	config dummy points per pattern
	 *	@return	array	pattern_key => points
	 */
	public static function build_dummy_points_map($candidates, $type, $base_points)
	{
		$n = count($candidates);
		$map = [];
		if( $n == 0 )
		{
			return $map;
		}

		$ids = [];
		$weights = [];
		$sum = 0.0;
		foreach( $candidates as $candidate )
		{
			$ids[] = $candidate->id;
			$w = $candidate->dummy_weight;
			if( $w === null || $w <= 0 )
			{
				$w = 0.0;
			}
			$weights[$candidate->id] = (float)$w;
			$sum += (float)$w;
		}
		if( $sum <= 0.0 )
		{
			$equal = 1.0 / (float)$n;
			foreach( $ids as $id )
			{
				$weights[$id] = $equal;
			}
		}
		else
		{
			foreach( $weights as $id => $w )
			{
				$weights[$id] = $w / $sum;
			}
		}

		if( $type == Bet::TYPE_WIN )
		{
			$total_pool = (float)$base_points * (float)$n;
			foreach( $ids as $id )
			{
				$map[$id . '_-1_-1'] = $total_pool * $weights[$id];
			}
			return $map;
		}

		// Harville: P(i then j) = w_i * w_j / (1 - w_i)
		$exacta_probs = [];
		$prob_sum = 0.0;
		for( $i = 0; $i < $n; ++$i )
		{
			for( $j = 0; $j < $n; ++$j )
			{
				if( $i == $j )
				{
					continue;
				}
				$id_i = $ids[$i];
				$id_j = $ids[$j];
				$wi = $weights[$id_i];
				$wj = $weights[$id_j];
				$denom = 1.0 - $wi;
				$p = ($denom <= 1e-12) ? 0.0 : ($wi * $wj / $denom);
				$exacta_probs[$id_i . '_' . $id_j] = $p;
				$prob_sum += $p;
			}
		}

		if( $type == Bet::TYPE_EXACTA )
		{
			$num_patterns = $n * ($n - 1);
			if( $num_patterns <= 0 )
			{
				return $map;
			}
			$total_pool = (float)$base_points * (float)$num_patterns;
			if( $prob_sum <= 0.0 )
			{
				$equal = $total_pool / (float)$num_patterns;
				foreach( $exacta_probs as $pair => $_p )
				{
					[$a, $b] = explode('_', $pair, 2);
					$map[$a . '_' . $b . '_-1'] = $equal;
				}
				return $map;
			}
			foreach( $exacta_probs as $pair => $p )
			{
				[$a, $b] = explode('_', $pair, 2);
				$map[$a . '_' . $b . '_-1'] = $total_pool * ($p / $prob_sum);
			}
			return $map;
		}

		if( $type == Bet::TYPE_QUINELLA )
		{
			$num_patterns = (int)(($n * ($n - 1)) / 2);
			if( $num_patterns <= 0 )
			{
				return $map;
			}
			$total_pool = (float)$base_points * (float)$num_patterns;
			for( $i = 0; $i < $n - 1; ++$i )
			{
				for( $j = $i + 1; $j < $n; ++$j )
				{
					$id_i = $ids[$i];
					$id_j = $ids[$j];
					$p = ($exacta_probs[$id_i . '_' . $id_j] ?? 0.0)
						+ ($exacta_probs[$id_j . '_' . $id_i] ?? 0.0);
					$key = $id_i . '_' . $id_j . '_-1';
					if( $prob_sum <= 0.0 )
					{
						$map[$key] = $total_pool / (float)$num_patterns;
					}
					else
					{
						$map[$key] = $total_pool * ($p / $prob_sum);
					}
				}
			}
			return $map;
		}

		return $map;
	}

	/**
	 *  Update game's odds
	 */
	public function update_odds()
	{
		$candidates = Candidate::where('game_id', $this->id)
			->orderBy('id', 'asc')
			->select('id', 'dummy_weight')
			->get();
		if( count($candidates) > 0 )
		{
			RuleWin::update_odds(
				$this->id,
				$candidates,
				self::build_dummy_points_map($candidates, Bet::TYPE_WIN, config('odds.dummy_points'))
			);
			if( $this->is_enabled(Bet::TYPE_QUINELLA) )
			{
				RuleQuinella::update_odds(
					$this->id,
					$candidates,
					self::build_dummy_points_map($candidates, Bet::TYPE_QUINELLA, config('odds.dummy_quinella_points'))
				);
			}
			if( $this->is_enabled(Bet::TYPE_EXACTA) )
			{
				RuleExacta::update_odds(
					$this->id,
					$candidates,
					self::build_dummy_points_map($candidates, Bet::TYPE_EXACTA, config('odds.dummy_exacta_points'))
				);
			}
		}

		$this->visibility = Bet::where('game_id', $this->id)->distinct('user_id')->count();
		$this->next_update = date('Y/m/d H:i:s', strtotime('+' . config('odds.interval_calc_odds')));
		// Mark clean so cron skips until the next bet sets exclusion_update back to 0.
		$this->exclusion_update = 1;
		$this->update();
		Cache::forget(self::public_view_cache_key($this->id));
	}

	/**
	 *	Cache key for public race view payload
	 */
	public static function public_view_cache_key($game_id) : string
	{
		return 'game_odds_view:' . $game_id;
	}

	/**
	 *	Public race page data shared across visitors (file cache, 10s TTL).
	 *	Does not include per-user bets or points.
	 */
	public function get_public_view_data() : array
	{
		$game = $this;
		return Cache::remember(self::public_view_cache_key($this->id), 10, function () use($game)
			{
				$candidates = Candidate::where('game_id', $game->id)
					->orderBy('disp_order', 'asc')
					->select('id', 'name', 'disp_order', 'result_rank', 'frame_color')
					->get();
				Candidate::fill_missing_frame_colors($candidates, $game->id);

				return [
					'candidates' => $candidates,
					'candidate_history' => Candidate::get_past_results(
						$game->user_id,
						$candidates->pluck('name')->all()
					),
					'odds0' => Odd::where('game_id', $game->id)->where('type', 0)
						->select('candidate_id0', 'odds', 'favorite')
						->get(),
					'odds1' => Odd::where('game_id', $game->id)->where('type', 1)
						->select('candidate_id0', 'candidate_id1', 'odds')
						->get(),
					'odds2' => Odd::where('game_id', $game->id)->where('type', 2)
						->select('candidate_id0', 'candidate_id1', 'odds')
						->get(),
					'starter_count' => count($candidates),
					'participant_count' => intval(Game::where('id', $game->id)->value('visibility')),
				];
			}
		);
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
