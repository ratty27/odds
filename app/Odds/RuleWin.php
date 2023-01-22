<?php

namespace App\Odds;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Game;
use App\Models\Candidate;
use App\Models\Odd;
use App\Models\Bet;
use App\Odds\RuleBase;

class RuleWin extends RuleBase
{
	/**
	 *	Get rule type ID
	 */
	public static function get_typeid() : int
	{
		return Bet::TYPE_WIN;
	}

	/**
	 *	Get rule signature
	 *	@return Signature string
	 */
	public static function get_signature() : string
	{
		return 'win';
	}

	/**
	 *	Get patterns of bet
	 *	@param	$candidates		Array of candidates thhat is sorted by ID
	 *	@return	Array of string of betting patterns
	 */
	public static function get_patterns($candidates) : array
	{
		$ret = array();
		foreach( $candidates as &$candidate )
		{
			$ret[] = array($candidate->id);
		}
		return $ret;
	}

	/**
	 *	Check whether bet and pattern is matched
	 */
	public static function is_patten_matched($bet, $pat) : bool
	{
		return $bet->candidate_id0 == $pat[0];
	}

	/**
	 *	Get current odds of this rule
	 */
	public static function get_odds($game_id)
	{
		return Odd::where('game_id', $game_id)->where('type', static::get_typeid())
			->select('candidate_id0', 'odds')->get();
	}

	/**
	 *	Check whether a bet is matched
	 */
	public static function is_bet_matched($bet) : bool
	{
		return $bet->rank0 == 1;
	}

	/**
	 *	Check whether a bet is match a odd
	 */
	public static function is_odds_matched($bet, $odd) : bool
	{
		return $odd->candidate_id0 == $bet->candidate_id0;
	}
}
