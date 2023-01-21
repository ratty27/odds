<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Game;
use App\Models\Candidate;
use App\Models\Odd;
use App\Models\Bet;
use App\Odds\RuleWin;
use App\Odds\RuleQuinella;
use App\Odds\RuleExacta;

class GameController extends Controller
{
	/**
	 *  Top page
	 */
	public function index()
	{
		if( !User::is_valid_user() )
		{
			if( config('odds.confirm_robot') )
			{
				return User::auth_login();
			}
			else
			{
				DB::transaction(function ()
					{
						$token = User::generate_token();
						User::register_user($token, config('odds.initial_points'));
						Cookie::queue('iden_token', $token, 60*24*365*2);
					} );
			}
		}

		return view('game/index');
	}

	/**
	 *  Edit a game (admin)
	 */
	public function edit($game_id)
	{
		if( !User::is_valid_user() )
		{
			return User::auth_login();
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
		if( !User::is_valid_user() )
		{
			return User::auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			DB::transaction(function () use($request, $user)
				{
					$name = $request->input('game_name');
					$limit = $request->input('game_limit');
					$comment = $request->input('game_comment');
					if( $comment == null )
					{
						$comment = '';
					}

					$enabled = 1;	// 'win' is awlways enabled
					$in_enabled = $request->input('enabled');
					if( $in_enabled != null )
					{
						foreach( $in_enabled as $enabled_index )
						{
							$index = intval( $enabled_index );
							$enabled |= 1 << $index;
						}
					}

					$candidate_names = explode("\n", $request->input('game_candidate'));
					$candidate_names = array_map('trim', $candidate_names);

					// Update a game info.
					$game_id = $request->input('game_id');
					if( $game_id === 'new' )
					{
						Game::new_game($user->id, $name, $limit, $comment, $enabled, $candidate_names);
					}
					else
					{
						$game = Game::find($game_id);
						if( !is_null($game) )
						{
							$game->update_game($name, $limit, $comment, $enabled, $candidate_names);
						}
					}
				}
			);
		}
		return redirect('/');
	}

	/**
	 *  Delete a game (admin)
	 */
	public function delete_game($game_id)
	{
		if( !User::is_valid_user() )
		{
			return User::auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			DB::transaction(function () use($game_id)
				{
					$game = Game::find($game_id);
					if( !is_null($game) )
					{
						$game->safe_delete();
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
		if( !User::is_valid_user() )
		{
			return User::auth_login();
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
		if( !User::is_valid_user() )
		{
			return User::auth_login();
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
		if( !User::is_valid_user() )
		{
			return User::auth_login();
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
		if( !User::is_valid_user() )
		{
			return User::auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		if( $user->admin )
		{
			DB::transaction(function () use($request, $user)
				{
					$game_id = $request->input('game_id');
					$game = Game::find($game_id);
					if( !is_null($game) )
					{
						$candidates = Candidate::where('game_id', $game_id)->select('id')->get();
						foreach( $candidates as $candidate )
						{
							$ranking = intval( $request->input('ranking_' . $candidate->id) );
							if( $ranking <= 0 )
							{	// Invalid ranking value
								throw new Exception(__('odds.internal_error'));
							}
							$candidate->result_rank = $ranking;
							$candidate->save();
						}

						$game->finish();
					}
				});
		}
		return redirect('/');
	}

	/**
	 *  Show a game
	 */
	public function show($game_id)
	{
		if( !User::is_valid_user() )
		{
			return User::auth_login();
		}

		return view('game/show', compact('game_id'));
	}

	/**
	 *  Bet in a game
	 */
	public function bet($game_id)
	{
		if( !User::is_valid_user() )
		{
			return User::auth_login();
		}

		$game = Game::findOrFail($game_id);
		if( $game->status > 0 )
		{
			return redirect('/game/' . $game_id);
		}
		$game->update_odds_if_needs();
		return view('game/bet', compact('game'));
	}

	/**
	 *	Calculate total bet points
	 */
	private static function total_bet($request, $sig, $pattern, &$points)
	{
		$total = 0;
		foreach( $pattern as &$pat )
		{
			$num = $request->input('bet_' . $sig . '_' . implode('_', $pat));
			if( is_numeric($num) )
			{
				$pt = intval($num);
				$total += $pt;
			}
			else
			{
				$pt = 0;
			}
			$points[] = $pt;
		}
		return $total;
	}

	/**
	 *  Save betting info
	 */
	public function save_bet(Request $request)
	{
		if( !User::is_valid_user() )
		{
			return User::auth_login();
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
				// - for win
				$pattern_win = RuleWin::get_patterns($candidates);
				$request_points_win = array();
				$request_bets += self::total_bet($request, RuleWin::get_signature(), $pattern_win, $request_points_win);
				// - for quinella
				$pattern_quinella = RuleQuinella::get_patterns($candidates);
				$request_points_quinella = array();
				if( $game->is_enabled(Bet::TYPE_QUINELLA) )
				{
					$request_bets += self::total_bet($request, RuleQuinella::get_signature(), $pattern_quinella, $request_points_quinella);
				}
				// - for exacta
				$pattern_exacta = RuleExacta::get_patterns($candidates);
				$request_points_exacta = array();
				if( $game->is_enabled(Bet::TYPE_EXACTA) )
				{
					$request_bets += self::total_bet($request, RuleExacta::get_signature(), $pattern_exacta, $request_points_exacta);
				}

				// Check whether request bets over own points
				$left = $user->get_current_points() + $last_bets - $request_bets;
				if( $left < 0 )
				{
					Log::warning('Invalid bettnig request : user_id=' . $user->id . ' / current=' . ($left + $request_bets) . ' / requested=' . $request_bets);
					return;
				}

				// Save betting request
				// - for win
				RuleWin::save_bet( $game_id, $user->id, $pattern_win, $request_points_win );
				// - for quinella
				if( $game->is_enabled(Bet::TYPE_QUINELLA) )
				{
					RuleQuinella::save_bet( $game_id, $user->id, $pattern_quinella, $request_points_quinella );
				}
				// - for exacta
				if( $game->is_enabled(Bet::TYPE_EXACTA) )
				{
					RuleExacta::save_bet( $game_id, $user->id, $pattern_exacta, $request_points_exacta );
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
	 *  Error
	 */
	public function error($errcode)
	{
		return response(__($errcode), 500)->header('Content-Type', 'text/plain');
	}
}
