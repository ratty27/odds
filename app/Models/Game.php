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
	 *  Update game's odds
	 */
	public function update_odds()
	{
		$game_id = $this->id;

		// for win
		$candidates = Candidate::where('game_id', $game_id)->select('id')->get();
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
			// Ranking of favorite
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
		}

		$this->next_update = date('Y/m/d H:i:s', strtotime('+' . config('odds.interval_calc_odds')));
		$this->update();
	}

	/**
	 *  Update game's odds
	 */
	public function update_odds_if_needs()
	{
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
