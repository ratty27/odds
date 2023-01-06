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

class Game extends Model
{
	use HasFactory;

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
		$game_id = $this->id;

		// for win
		$candidates = Candidate::where('game_id', $game_id)
			->orderBy('disp_order', 'asc')
			->select('id')->get();
		if( count($candidates) > 0 )
		{
			// Calculate odds for each candidate
			$dummy = config('odds.dummy_points') / count($candidates);
			$total_bets = intval( Bet::where('game_id', $game_id)->where('type', 0)->sum('points') ) + ($dummy * count($candidates));
			$results = array();
			foreach( $candidates as $candidate )
			{
				$candidate_bet = intval( Bet::where('type', 0)->where('candidate_id0', $candidate->id)->sum('points') ) + $dummy;
				if( $candidate_bet <= 0 )
					$candidate_bet = 1;
				$odds_value = round((float)$total_bets / (float)$candidate_bet, 1);
				//Log::info('Odds ' . $candidate->id . ': ' . $total_bets . ' / ' . $candidate_bet . ' = ' . $odds_value );

				$results[] = array('id' => $candidate->id, 'odds' => $odds_value);
			}
			usort( $results, [Game::class, "compare_odds"] );
			// Write result w/ calculation of ranking of favorite
			$rank = 0;
			$last = 0.0;
			for( $i = 0; $i < count($results); ++$i )
			{
				$result = $results[$i];
				if( $last < $result['odds'] )
				{
					$last = $result['odds'];
					++$rank;
				}

				$odds = Odd::where('type', 0)->where('candidate_id0', $result['id'])->select('id')->get();
				if( count($odds) > 0 )
				{
					$odds[0]->odds = $result['odds'];
					$odds[0]->favorite = $rank;
					$odds[0]->update();
				}
				else
				{
					$odd = new Odd;
					$odd->game_id = $game_id;
					$odd->type = 0;
					$odd->candidate_id0 = $result['id'];
					$odd->odds = $result['odds'];
					$odd->favorite = $rank;
					$odd->save();
				}
			}
			// for quinella
			if( $this->is_enabled(1) )
			{
				$num = count($candidates);
				$num = (($num * $num) - $num) / 2;
				$dummy = config('odds.dummy_quinella_points');
				$total_bets = intval( Bet::where('game_id', $game_id)->where('type', 1)->sum('points') ) + ($dummy * $num);
				$results = array();
				for( $i = 0; $i < count($candidates) - 1; ++$i )
				{
					for( $j = $i + 1; $j < count($candidates); ++$j )
					{
						$id0 = $candidates[$i]->id;
						$id1 = $candidates[$j]->id;
						if( $id0 > $id1 )
						{
							$tmp = $id0;
							$id0 = $id1;
							$id1 = $tmp;
						}
						$candidate_bet = intval( Bet::where('type', 1)->where('candidate_id0', $id0)->where('candidate_id1', $id1)->sum('points') ) + $dummy;
						if( $candidate_bet <= 0 )
							$candidate_bet = 1;
						$odds_value = round((float)$total_bets / (float)$candidate_bet, 1);

						$results[] = array('id0' => $id0, 'id1' => $id1, 'odds' => $odds_value);
					}
				}
				for( $i = 0; $i < count($results); ++$i )
				{
					$result = $results[$i];
					$odds = Odd::where('type', 1)
						->where('candidate_id0', $result['id0'])
						->where('candidate_id1', $result['id1'])
						->select('id')->get();
					if( count($odds) > 0 )
					{
						$odds[0]->odds = $result['odds'];
						$odds[0]->update();
					}
					else
					{
						$odd = new Odd;
						$odd->game_id = $game_id;
						$odd->type = 1;
						$odd->candidate_id0 = $result['id0'];
						$odd->candidate_id1 = $result['id1'];
						$odd->odds = $result['odds'];
						$odd->save();
					}
				}
			}
			// for exacta
			if( $this->is_enabled(2) )
			{
				$num = count($candidates);
				$num = (($num * $num) - $num);
				$dummy = config('odds.dummy_exacta_points');
				$total_bets = intval( Bet::where('game_id', $game_id)->where('type', 2)->sum('points') ) + ($dummy * $num);
				$results = array();
				for( $i = 0; $i < count($candidates); ++$i )
				{
					for( $j = 0; $j < count($candidates); ++$j )
					{
						if( $i == $j )
						{
							continue;
						}

						$candidate_bet = intval( Bet::where('type', 2)->where('candidate_id0', $candidates[$i]->id)->where('candidate_id1', $candidates[$j]->id)->sum('points') ) + $dummy;
						if( $candidate_bet <= 0 )
							$candidate_bet = 1;
						$odds_value = round((float)$total_bets / (float)$candidate_bet, 1);
						//Log::info('Odds ' . $candidate->id . ': ' . $total_bets . ' / ' . $candidate_bet . ' = ' . $odds_value );

						$id0 = $candidates[$i]->id;
						$id1 = $candidates[$j]->id;
						$results[] = array('id0' => $id0, 'id1' => $id1, 'odds' => $odds_value);
					}
				}
				for( $i = 0; $i < count($results); ++$i )
				{
					$result = $results[$i];
					$odds = Odd::where('type', 2)
						->where('candidate_id0', $result['id0'])
						->where('candidate_id1', $result['id1'])
						->select('id')->get();
					if( count($odds) > 0 )
					{
						$odds[0]->odds = $result['odds'];
						$odds[0]->update();
					}
					else
					{
						$odd = new Odd;
						$odd->game_id = $game_id;
						$odd->type = 2;
						$odd->candidate_id0 = $result['id0'];
						$odd->candidate_id1 = $result['id1'];
						$odd->odds = $result['odds'];
						$odd->save();
					}
				}
			}
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
