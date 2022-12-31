<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Login;
use App\Models\User;
use App\Models\Game;
use App\Models\Candidate;
use App\Models\Odd;
use App\Models\Bet;

class GameController extends Controller
{
	/**
	 *  Check whether cookie has valid token
	 */
	public function is_valid_user()
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
    protected function generate_token()
    {
        // Todo: Generate w/ secure random, if php8.2 later.
        return hash('sha256', uniqid(config('app.key')) . random_int(1000000, 9999999));
    }

	/**
	 *  Top page
	 */
	public function index()
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		return view('game/index');
	}

	/**
	 *	Authorize
	 */
	public function auth_login()
	{
		$token = $this->generate_token();
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
	 *	Login
	 */
	public function login($token)
	{
		$login = Login::where('token', $token)->select('id')->get();
		if( count($login) > 0 )
		{
			$login[0]->delete();
            User::register_user($token, config('odds.initial_points'));
            Cookie::queue('iden_token', $token, 60*24*365*2);
		}
		return redirect('/');
	}

	/**
	 *  Edit a game (admin)
	 */
	public function edit($game_id)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			return view('game/edit', compact('game_id'));
		}
		else
		{
			return redirect('/');
		}
	}

	/**
	 *  Update a game (admin)
	 */
	public function update(Request $request)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			DB::transaction(function () use($request, $user)
				{
					$game_id = $request->input('game_id');
					// Update a game info.
					if( $game_id === 'new' )
					{
						$game = new Game;
					}
					else
					{
						$game = Game::find($game_id);
					}
					$game->name = $request->input('game_name');
					$game->limit = $request->input('game_limit');
					$game->comment = $request->input('game_comment');
					if( $game->comment == null )
					{
						$game->comment = '';
					}
					$game->user_id = $user->id;
					$game->next_update = date("Y/m/d H:i:s");
					$game->exclusion_update = 0;

					$game->enabled = 0;
					$enabled = $request->input('enabled');
					foreach( $enabled as $enabled_index )
					{
						$index = intval( $enabled_index );
						$game->enabled |= 1 << $index;
					}

					//Log::info('Update game: ' . $request->input('game_name'));
					if( $game->save() )
					{   // Update cadidates
						$candidate_names = explode("\n", $request->input('game_candidate'));
						$candidate_names = array_map('trim', $candidate_names);

						$candidate_updated = array();

						// Update existing records
						$candidates = Candidate::where('game_id', $game->id)
							->select('id', 'name', 'disp_order')
							->get();
						foreach( $candidates as &$candidate )
						{
							$index = array_search($candidate->name, $candidate_names);
							if( $index === false )
							{
								Odd::where('candidate_id0', $candidate->id)
								 ->orWhere('candidate_id1', $candidate->id)
								 ->orWhere('candidate_id2', $candidate->id)
								 ->delete();
								Bet::where('candidate_id0', $candidate->id)
								 ->orWhere('candidate_id1', $candidate->id)
								 ->orWhere('candidate_id2', $candidate->id)
								 ->delete();
								$candidate->delete();
							}
							else
							{
								if( $candidate->disp_order != $index )
								{
									$candidate->disp_order = $index;
									$candidate->update();
								}
								array_push($candidate_updated, $candidate->name);
							}
						}

						// Add new records
						for($index = 0; $index < count($candidate_names); ++$index)
						{
							if( !in_array($candidate_names[$index], $candidate_updated) )
							{
								$candidate = new Candidate;
								$candidate->name = $candidate_names[$index];
								$candidate->game_id = $game->id;
								$candidate->disp_order = $index;
								$candidate->save();
							}
						}

						$game->update_odds();
					}
				}
			);
		}
		return redirect('/');
	}

	/**
	 *  Close a game (admin)
	 */
	public function close($game_id)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			DB::transaction(function () use($game_id)
				{
					$game = Game::find($game_id);
					if( $game->status == 0 )
					{
						$game->status = 1;
						$game->update_odds();
					}
				}
			);
		}
		return redirect('/');
	}

	/**
	 *  Re-open a game (admin)
	 */
	public function reopen($game_id)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			DB::transaction(function () use($game_id)
				{
					$game = Game::find($game_id);
					if( $game->status == 1 )
					{
						$game->status = 0;
						$game->update();
					}
				}
			);
		}
		return redirect('/');
	}

	/**
	 *  Input result of a game (admin)
	 */
	public function result($game_id)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			return view('game/result', compact('game_id'));
		}
		else
		{
			return redirect('/');
		}
	}

	/**
	 *  Finish a game (admin)
	 */
	public function finish(Request $request)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			DB::transaction(function () use($request, $user)
				{
					$game_id = $request->input('game_id');
					$game = Game::find($game_id);

					$candidates = Candidate::where('game_id', $game_id)->select('id')->get();
					foreach( $candidates as $candidate )
					{
						$ranking = intval( $request->input('ranking_' . $candidate->id) );
						if( $ranking <= 0 )
						{	// Invalid ranking value
							throw new Exception(__('internal_error'));
						}
						$candidate->result_rank = $ranking;
						$candidate->save();
					}

					$game->status = 2;
					$game->update_odds();
					$success = true;
				});
		}
		return redirect('/');
	}

	/**
	 *  Show a game
	 */
	public function show($game_id)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		return view('game/show', compact('game_id'));
	}

	/**
	 *  Bet in a game
	 */
	public function bet($game_id)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		return view('game/bet', compact('game_id'));
	}

	/**
	 *  Save betting info
	 */
	public function save_bet(Request $request)
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		$game_id = $request->input('game_id');
		DB::transaction(function () use($request, $user, $game_id)
			{
				$game = Game::find($game_id);
				if( $game->status > 0 )
				{	// Reject
					return;
				}

				$candidates = Candidate::where('game_id', $game_id)->where('result_rank', '<', 0)
					->orderBy('id', 'asc')->select('id', 'disp_order')->get();
				$last_bets = intval(
					Bet::where('game_id', $game_id)->where('user_id', $user->id)->where('payed', 0)->sum('points')
				);

				// Requested total bets
				$request_bets = 0;
				// for win
				foreach( $candidates as &$candidate )
				{
					$request_bets += $request->input('bet_win_' . $candidate->id);
				}

				// Check whether request bets over own points
				$left = $user->get_current_points() + $last_bets - $request_bets;
				if( $left < 0 )
				{
					Log::warning('Invalid bettnig request : user_id=' . $user->id);
					return;
				}

				// Save betting request
				// for win
				$bets = Bet::where('game_id', $game_id)
					->where('user_id', $user->id)
					->where('type', 0)
					->where('payed', 0)
					->orderBy('candidate_id0', 'asc')
					->select('id', 'points', 'candidate_id0')->get();
				$beti = 0;
				foreach( $candidates as &$candidate )
				{
					$bet_points = $request->input('bet_win_' . $candidate->id);
					if( $beti < count($bets) )
					{
						if( $bets[$beti]->candidate_id0 == $candidate->id )
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
						$bet->type = 0;
						$bet->game_id = $game_id;
						$bet->user_id = $user->id;
						$bet->candidate_id0 = $candidate->id;
						$bet->points = $bet_points;
						$bet->payed = 0;
						$bet->save();
					}
				}
				// for quinella
				if( $game->is_enabled(1) )
				{
					$bets = Bet::where('game_id', $game_id)
						->where('user_id', $user->id)
						->where('type', 1)
						->where('payed', 0)
						->orderBy('candidate_id0', 'asc')
						->orderBy('candidate_id1', 'asc')
						->select('id', 'points', 'candidate_id0', 'candidate_id1')->get();
					$beti = 0;
					for( $i = 0; $i < count($candidates) - 1; ++$i )
					{
						for( $j = $i + 1; $j < count($candidates); ++$j )
						{
							$id0 = $candidates[$i]->id;
							$id1 = $candidates[$j]->id;
							$bet_points = $request->input('bet_quinella_' . $id0 . '_' . $id1);
							if( $beti < count($bets) )
							{
								if( $bets[$beti]->candidate_id0 == $id0
								 && $bets[$beti]->candidate_id1 == $id1 )
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
								$bet->type = 1;
								$bet->game_id = $game_id;
								$bet->user_id = $user->id;
								$bet->candidate_id0 = $id0;
								$bet->candidate_id1 = $id1;
								$bet->points = $bet_points;
								$bet->payed = 0;
								$bet->save();
							}
						}
					}
				}
				// for exacta
				if( $game->is_enabled(2) )
				{
					$bets = Bet::where('game_id', $game_id)
						->where('user_id', $user->id)
						->where('type', 2)
						->where('payed', 0)
						->orderBy('candidate_id0', 'asc')
						->orderBy('candidate_id1', 'asc')
						->select('id', 'points', 'candidate_id0', 'candidate_id1')->get();
					$beti = 0;
					for( $i = 0; $i < count($candidates); ++$i )
					{
						for( $j = 0; $j < count($candidates); ++$j )
						{
							if( $i == $j ) continue;
							$id0 = $candidates[$i]->id;
							$id1 = $candidates[$j]->id;
							$bet_points = $request->input('bet_exacta_' . $id0 . '_' . $id1);
							if( $beti < count($bets) )
							{
								if( $bets[$beti]->candidate_id0 == $id0
								 && $bets[$beti]->candidate_id1 == $id1 )
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
								$bet->type = 2;
								$bet->game_id = $game_id;
								$bet->user_id = $user->id;
								$bet->candidate_id0 = $id0;
								$bet->candidate_id1 = $id1;
								$bet->points = $bet_points;
								$bet->payed = 0;
								$bet->save();
							}
						}
					}
				}

				// Request to update odds
				if( !config('odds.calc_odds_on_request') )
				{
					$game->exclusion_update = 0;
					$game->update();
				}
			}
		);
		return redirect()->action('App\Http\Controllers\GameController@show', ['game_id' => $game_id]);
	}

	/**
	 *  Force reset user points
	 */
	public function reset_user()
	{
		if( !$this->is_valid_user() )
		{
			return $this->auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		DB::transaction(function () use($user)
			{
				// Todo: Record to log
				Bet::where('user_id', $user->id)->where('payed', 0)->delete();
				$user->points = config('odds.initial_points');
				$user->update();
			}
		);
		return redirect('/');
	}

	/**
	 *  Error
	 */
	public function error($errcode)
	{
		return response(__($errcode), 500)->header('Content-Type', 'text/plain');
	}
}
