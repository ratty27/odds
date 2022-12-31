<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Game;
use App\Models\Candidate;
use App\Models\Bet;

class User extends Model
{
	use HasFactory;

	/**
	 *  Check whether an ID is exists.
	 */
	public static function exists_user($iden)
	{
		$record = User::where('personal_id', $iden)->take(1)->get();
		return count($record) > 0;
	}

	/**
	 *  Regiter new user
	 */
	public static function register_user($iden, $points)
	{
		$new_user = new User;
		$new_user->name = '';
		$new_user->personal_id = $iden;
		$new_user->points = $points;
		$new_user->admin = 0;
		return $new_user->save();
	}

	/**
	 *  Get betting points
	 */
	public function get_betting_points()
	{
		return intval( DB::table('bets')->where('user_id', $this->id)->where('payed', 0)->sum('points') );
	}

	/**
	 *  Get current points left
	 */
	public function get_current_points()
	{
		return $this->points - $this->get_betting_points();
	}

	/**
	 *  Receive rewards if exists
	 */
	public function ReceiveRewards()
	{
		$user_id = $this->id;
		DB::transaction(function () use($user_id)
			{
				$user = User::find($user_id);
				$rewards = 0;
				$finished_list = DB::table('bets')->where('bets.user_id', $this->id)->where('bets.payed', 0)->select('games.id as gid')
					->join('games', 'games.id', '=', 'bets.game_id')->where('games.status', 2)->distinct()->get();
				foreach( $finished_list as $finished )
				{
					$bets = Bet::where('bets.user_id', $user_id)
						->where('bets.game_id', $finished->gid)
						->where('bets.payed', 0)
						->select('bets.id', 'bets.type', 'bets.candidate_id0', 'bets.candidate_id1', 'bets.candidate_id2', 'bets.points', 'can0.result_rank as rank0', 'can1.result_rank as rank1', 'can2.result_rank as rank2')
						->leftJoin('candidates as can0', 'can0.id', '=', 'bets.candidate_id0')
						->leftJoin('candidates as can1', 'can1.id', '=', 'bets.candidate_id1')
						->leftJoin('candidates as can2', 'can2.id', '=', 'bets.candidate_id2')->get();
					//Log::info('Result: ' . json_encode($bets));

					$odds0 = Odd::where('game_id', $finished->gid)->where('type', 0)
						->select('candidate_id0', 'odds')->get();
					$odds1 = Odd::where('game_id', $finished->gid)->where('type', 1)
						->select('candidate_id0', 'candidate_id1', 'odds')->get();
					$odds2 = Odd::where('game_id', $finished->gid)->where('type', 2)
						->select('candidate_id0', 'candidate_id1', 'odds')->get();

					foreach( $bets as $bet )
					{
						$user->points -= $bet->points;
						Log::channel('oddslog')->info('BET: ', ['id' => $bet->id, 'game_id' => $finished->gid, 'user_id' => $user->id, 'bet' => $bet->points]);
						switch($bet->type)
						{
						// win
						case 0:
							if( $bet->rank0 == 1 )
							{
								foreach( $odds0 as $odd )
								{
									if( $odd->candidate_id0 == $bet->candidate_id0 )
									{
										$rewards += (int)($bet->points * $odd->odds);
										Log::channel('oddslog')->info('PAY OFF: ', ['id' => $bet->id, 'game_id' => $finished->gid, 'user_id' => $user->id, 'bet' => $bet->points, 'odds' => $odd->odds]);
										break;
									}
								}
							}
							break;

						// quinella
						case 1:
							if( ($bet->rank0 == 1 && $bet->rank1 == 2)
							 || ($bet->rank0 == 2 && $bet->rank1 == 1)
							 || ($bet->rank0 == 1 && $bet->rank1 == 1) )
							{
								foreach( $odds1 as $odd )
								{
									if( ($odd->candidate_id0 == $bet->candidate_id0)
									 && ($odd->candidate_id1 == $bet->candidate_id1) )
									{
										$rewards += (int)($bet->points * $odd->odds);
										Log::channel('oddslog')->info('PAY OFF: ', ['id' => $bet->id, 'game_id' => $finished->gid, 'user_id' => $user->id, 'bet' => $bet->points, 'odds' => $odd->odds]);
										break;
									}
								}
							}
							break;

						// exacta
						case 2:
							if( ($bet->rank0 == 1 && $bet->rank1 == 2)
							 || ($bet->rank0 == 1 && $bet->rank1 == 1) )
							{
								foreach( $odds2 as $odd )
								{
									if( ($odd->candidate_id0 == $bet->candidate_id0)
									 && ($odd->candidate_id1 == $bet->candidate_id1) )
									{
										$rewards += (int)($bet->points * $odd->odds);
										Log::channel('oddslog')->info('PAY OFF: ', ['id' => $bet->id, 'game_id' => $finished->gid, 'user_id' => $user->id, 'bet' => $bet->points, 'odds' => $odd->odds]);
										break;
									}
								}
							}
							break;

						default:
							break;
						}
						$bet->payed = 1;
						$bet->update();
					}
				}
				// Todo: Record to log...
				$user->points += $rewards;
				$user->update();
			}
		);
	}
}
