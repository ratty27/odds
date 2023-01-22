<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Game;
use App\Models\Candidate;
use App\Models\Bet;
use App\Odds\RuleWin;
use App\Odds\RuleQuinella;
use App\Odds\RuleExacta;

class User extends Model
{
	use HasFactory;

	/**
	 *  Check whether cookie has valid token
	 */
	public static function is_valid_user()
	{
        if( Cookie::has('iden_token') )
        {
            $iden_token = Cookie::get('iden_token');
            if( User::exists_user($iden_token) )
            {
            	return true;
            }
        }
        return false;
	}

    /**
     *  Generate unique identify token
     */
    public static function generate_token()
    {
        $token = '';
        do
        {
	        // Todo: Generate w/ secure random, if php8.2 later.
	        $token = hash('sha256', uniqid(config('app.key')) . random_int(1000000, 9999999));
	    } while( User::exists_user($token) );
	    return $token;
    }

	/**
	 *  Make hash
	 */
	public static function make_hash($src)
	{
		$salt = config('app.key');
		return hash('sha256', $src . $salt);
	}

	/**
	 *	Make temporary token
	 */
	public function make_temp_token()
	{
		$token = '';
		do {
			$token = hash('sha256', uniqid($this->personal_id, true) . mt_rand());
		} while( User::where('temp', $token)->exists() );
		return $token;
	}

	/**
	 *	Authorize
	 */
	public static function auth_login()
	{
		$token = User::generate_token();
		$login = new Login;
		$login->token = $token;
		if( $login->save() )
		{
			return view('auth/login', compact('token'));
		}
		else
		{
			return response(__('odds.internal_error'), 500)->header('Content-Type', 'text/plain');
		}
	}

	/**
	 *  Check whether an ID is exists.
	 */
	public static function exists_user($iden)
	{
		return User::where('personal_id', $iden)->exists();
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
		$new_user->temp_limit = date("Y/m/d H:i:s");
		if( $new_user->save() )
		{
			return $new_user->id;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *	Get current user
	 */
	public static function get_current_user()
	{
		$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
		return User::where('personal_id', $user_token)->first();
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
					$game = Game::find($finished->gid);

					$bets = Bet::where('bets.user_id', $user_id)
						->where('bets.game_id', $finished->gid)
						->where('bets.payed', 0)
						->select('bets.id', 'bets.type', 'bets.candidate_id0', 'bets.candidate_id1', 'bets.candidate_id2', 'bets.points', 'can0.result_rank as rank0', 'can1.result_rank as rank1', 'can2.result_rank as rank2')
						->leftJoin('candidates as can0', 'can0.id', '=', 'bets.candidate_id0')
						->leftJoin('candidates as can1', 'can1.id', '=', 'bets.candidate_id1')
						->leftJoin('candidates as can2', 'can2.id', '=', 'bets.candidate_id2')->get();
					//Log::info('Result: ' . json_encode($bets));

					$odds0 = RuleWin::get_odds($finished->gid);
					if( $game->is_enabled(Bet::TYPE_QUINELLA) )
					{
						$odds1 = RuleQuinella::get_odds($finished->gid);
					}
					if( $game->is_enabled(Bet::TYPE_EXACTA) )
					{
						$odds2 = RuleExacta::get_odds($finished->gid);
					}

					foreach( $bets as $bet )
					{
						$user->points -= $bet->points;
						Log::channel('oddslog')->info('BET: ', ['id' => $bet->id, 'game_id' => $finished->gid, 'user_id' => $user->id, 'bet' => $bet->points]);
						switch($bet->type)
						{
						// win
						case 0:
							$user->points += RuleWin::payoff($finished->gid, $user->id, $bet, $odds0);
							break;

						// quinella
						case 1:
							$user->points += RuleQuinella::payoff($finished->gid, $user->id, $bet, $odds1);
							break;

						// exacta
						case 2:
							$user->points += RuleExacta::payoff($finished->gid, $user->id, $bet, $odds2);
							break;

						default:
							break;
						}
						$bet->payed = 1;
						$bet->update();
					}
				}
				$user->points += $rewards;
				$user->update();
			}
		);
	}

	/**
	 *	Delete this user
	 *
	 *	@remarks	This function is for only debug.
	 */
	public function safe_delete()
	{
		Bet::where('user_id', $this->id)->delete();

		$games = Game::where('user_id', $this->id)->get();
		foreach( $games as $game )
		{
			$game->safe_delete();
		}

		$this->delete();
	}

	/**
	 *  Cleanup no-bet users
	 */
	public static function	CleanupUsers()
	{
		$users = User::where('admin', 0)->whereNotIn('id', function($q)
			{
				$q->select('user_id')->from('bets');
			})->delete();
	}

	/**
	 *	Check whether the user has the right of edit game
	 */
	public function CanEditGame()
	{
		return $this->admin || $this->authorized == 3;
	}
}
