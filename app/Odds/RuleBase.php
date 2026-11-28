<?php

namespace App\Odds;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Game;
use App\Models\Candidate;
use App\Models\Odd;
use App\Models\Bet;

class RuleBase
{
	/**
	 *	Get rule type ID
	 */
	public static function get_typeid() : int
	{
		return -1;
	}

	/**
	 *	Get rule signature
	 *	@return Signature string
	 */
	public static function get_signature() : string
	{
		return '';
	}

	/**
	 *	Get patterns of bet
	 *	@param	$candidates		Array of candidates thhat is sorted by ID
	 *	@return	Array of string of betting patterns
	 */
	public static function get_patterns($candidates) : array
	{
		return array();
	}

	/**
	 *	Check whether bet and pattern is matched
	 */
	public static function is_patten_matched($bet, $pat) : bool
	{
		return false;
	}

	/**
	 *	Build a map key for a bet/odds pattern
	 */
	protected static function pattern_key($pat) : string
	{
		$c0 = $pat[0];
		$c1 = count($pat) >= 2 ? $pat[1] : -1;
		$c2 = count($pat) >= 3 ? $pat[2] : -1;
		return $c0 . '_' . $c1 . '_' . $c2;
	}

	/**
	 *	Update odds of a game
	 *	@param	$game_id		Game ID
	 *	@param	$candidates		Array of candidates thhat is sorted by ID
	 *	@param	$dummy			Bet points by dummy user. Scalar applies the same
	 *							value to every pattern; array maps pattern_key => points.
	 */
	public static function update_odds($game_id, $candidates, $dummy)
	{
		$pattern = static::get_patterns($candidates);
		$type = static::get_typeid();
		if( count($pattern) == 0 )
		{
			return;
		}

		// One grouped query instead of per-pattern SUM
		$sum_rows = Bet::where('game_id', $game_id)
			->where('type', $type)
			->groupBy('candidate_id0', 'candidate_id1', 'candidate_id2')
			->select('candidate_id0', 'candidate_id1', 'candidate_id2', DB::raw('SUM(points) as total_points'))
			->get();
		$sum_map = array();
		foreach( $sum_rows as $row )
		{
			$key = $row->candidate_id0 . '_' . $row->candidate_id1 . '_' . $row->candidate_id2;
			$sum_map[$key] = intval($row->total_points);
		}

		// Calculate sum of bets (dummy applied per pattern)
		$total_bets = 0;
		$results = array();
		$ranks = array();
		for( $i = 0; $i < count($pattern); ++$i )
		{
			$pat = $pattern[$i];
			$key = static::pattern_key($pat);
			$dummy_points = is_array($dummy) ? (float)($dummy[$key] ?? 0) : (float)$dummy;
			$candidate_bet = ($sum_map[$key] ?? 0) + $dummy_points;
			if( $candidate_bet <= 0 )
				$candidate_bet = 1;
			$results[] = $candidate_bet;
			$total_bets += $candidate_bet;

			// Ranking
			$rank = 0;
			for( $j = 0; $j < $i; ++$j )
			{
				if( $candidate_bet > $results[$j] )
				{
					++$ranks[$j];
				}
				else if( $candidate_bet < $results[$j] )
				{
					++$rank;
				}
			}
			$ranks[] = $rank;
		}

		// Load existing odds rows once, then update/insert in memory
		$existing_rows = Odd::where('game_id', $game_id)->where('type', $type)
			->select('id', 'candidate_id0', 'candidate_id1', 'candidate_id2', 'odds', 'favorite')
			->get();
		$existing_map = array();
		foreach( $existing_rows as $row )
		{
			$key = $row->candidate_id0 . '_' . $row->candidate_id1 . '_' . $row->candidate_id2;
			$existing_map[$key] = $row;
		}

		for( $i = 0; $i < count($pattern); ++$i )
		{
			$pat = $pattern[$i];
			$key = static::pattern_key($pat);

			$odds_value = round((float)$total_bets / (float)$results[$i], 1);
			if( $odds_value < 1.1 )
				$odds_value = 1.1;
			$favorite = $ranks[$i] + 1;

			if( isset($existing_map[$key]) )
			{
				$odds = $existing_map[$key];
				$odds->odds = $odds_value;
				$odds->favorite = $favorite;
				$odds->update();
			}
			else
			{
				$odd = new Odd;
				$odd->game_id = $game_id;
				$odd->type = $type;
				$odd->candidate_id0 = $pat[0];
				if( count($pat) >= 2 )
				{
					$odd->candidate_id1 = $pat[1];
					if( count($pat) >= 3 )
					{
						$odd->candidate_id2 = $pat[2];
					}
				}
				$odd->odds = $odds_value;
				$odd->favorite = $favorite;
				$odd->save();
			}
		}
	}

	/**
	 *	Save betting points
	 *	@param	$game_id		Game ID
	 *	@param	$user_id		User ID
	 *	@param	$pattern		Returned value of get_patterns()
	 *	@param	$request_bets	Array of betting points
	 */
	public static function save_bet($game_id, $user_id, $pattern, $request_bets) : void
	{
		if( count($pattern) != count($request_bets) )
		{
			throw new Exception("Candidate and Bet numbers are not match.");
		}

		$bets = Bet::where('game_id', $game_id)
			->where('user_id', $user_id)
			->where('type', static::get_typeid())
			->where('payed', 0)
			->orderBy('candidate_id0', 'asc')
			->orderBy('candidate_id1', 'asc')
			->orderBy('candidate_id2', 'asc')
			->select('id', 'points', 'candidate_id0', 'candidate_id1', 'candidate_id2')->get();
		$beti = 0;
		for( $i = 0; $i < count($pattern); ++$i )
		{
			$candidate = $pattern[$i];
			$bet_points = $request_bets[$i];
			if( $beti < count($bets) )
			{
				if( static::is_patten_matched($bets[$beti], $candidate) )
				{
					if( $bet_points > 0 )
					{
						$bets[$beti]->points = $bet_points;
						$bets[$beti]->update();
					}
					else
					{
						$bets[$beti]->delete();
					}
					++$beti;
					continue;
				}
			}

			if( $bet_points > 0 )
			{
				$bet = new Bet;
				$bet->type = static::get_typeid();
				$bet->game_id = $game_id;
				$bet->user_id = $user_id;
				$bet->candidate_id0 = $candidate[0];
				if( count($candidate) >= 2 )
				{
					$bet->candidate_id1 = $candidate[1];
					if( count($candidate) >= 3 )
					{
						$bet->candidate_id2 = $candidate[2];
					}
				}
				$bet->points = $bet_points;
				$bet->payed = 0;
				$bet->save();
			}
		}
	}

	/**
	 *	Get current odds of this rule
	 */
	public static function get_odds($game_id)
	{
		return array();
	}

	/**
	 *	Check whether a bet is matched
	 */
	public static function is_bet_matched($bet) : bool
	{
		return false;
	}

	/**
	 *	Check whether a bet is match a odd
	 */
	public static function is_odds_matched($bet, $odd) : bool
	{
		return false;
	}

	/**
	 *	Get rewards of a bet
	 */
	public static function payoff($game_id, $user_id, $bet, $odds) : int
	{
		$rewards = 0;
		if( static::is_bet_matched($bet) )
		{
			foreach( $odds as $odd )
			{
				if( static::is_odds_matched($bet, $odd) )
				{
					$rewards += (int)($bet->points * $odd->odds);
					Log::channel('oddslog')->info('PAY OFF: ', ['id' => $bet->id, 'game_id' => $game_id, 'user_id' => $user_id, 'bet' => $bet->points, 'odds' => $odd->odds]);
				}
			}
		}
		return $rewards;
	}
}
