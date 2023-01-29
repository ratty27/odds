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
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			if( $user->CanEditGame() )
			{
				$game_user = $user->id;
				return view('game/index', compact('user', 'game_user'));
			}
		}
		return redirect('/');
	}

	/**
	 *	User game list
	 */
	public function usergames($user_id)
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			$game_user = $user_id;
			return view('game/index', compact('user', 'game_user'));
		}
		return redirect('/');
	}

	/**
	 *  Edit a game
	 */
	public function edit($game_id)
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			if( $user->CanEditGame() )
			{
				return view('game/edit', compact('user', 'game_id'));
			}
		}
		return redirect('/');
	}

	/**
	 *  Update a game
	 */
	public function update(Request $request)
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			if( $user->CanEditGame() )
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

						$pubset = intval( $request->input('game_pubsetting') );

						// Update a game info.
						$game_id = $request->input('game_id');
						if( $game_id === 'new' )
						{
							Game::new_game($user->id, $name, $limit, $comment, $enabled, $candidate_names, $pubset);
						}
						else
						{
							$game = Game::find($game_id);
							if( !is_null($game) )
							{
								if( $user->admin || $game->user_id == $user->id )
								{
									$game->update_game($name, $limit, $comment, $enabled, $candidate_names, $pubset);
								}
							}
						}
					}
				);
			}
		}
		return redirect('/mygames');
	}

	/**
	 *  Delete a game
	 */
	public function delete_game($game_id)
	{
		$user = User::get_current_user();
		$game = Game::find($game_id);
		if( !is_null($user) && !is_null($game) )
		{
			if( $user->admin || $game->user_id == $user->id )
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
		}
		return redirect('/mygames');
	}

	/**
	 *  Close a game
	 */
	public function close($game_id)
	{
		$user = User::get_current_user();
		$game = Game::find($game_id);
		if( !is_null($user) && !is_null($game) )
		{
			if( $user->admin || $game->user_id == $user->id )
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
		}
		return redirect('/mygames');
	}

	/**
	 *  Re-open a game
	 */
	public function reopen($game_id)
	{
		$user = User::get_current_user();
		$game = Game::find($game_id);
		if( !is_null($user) && !is_null($game) )
		{
			if( $user->admin || $game->user_id == $user->id )
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
		}
		return redirect('/mygames');
	}

	/**
	 *  Input result of a game
	 */
	public function result($game_id)
	{
		$user = User::get_current_user();
		$game = Game::find($game_id);
		if( !is_null($user) && !is_null($game) )
		{
			if( $user->admin || $game->user_id == $user->id )
			{
				return view('game/result', compact('user', 'game'));
			}
		}
		return redirect('/mygames');
	}

	/**
	 *  Finish a game
	 */
	public function finish(Request $request)
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			DB::transaction(function () use($request, $user)
				{
					$game_id = $request->input('game_id');
					$game = Game::find($game_id);
					if( !is_null($game) )
					{
						if( $user->admin || $game->user_id == $user->id )
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
					}
				}
			);
		}
		return redirect('/mygames');
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
		$limit = config('odds.limit_bet_points');
		$total = 0;
		foreach( $pattern as &$pat )
		{
			$num = $request->input('bet_' . $sig . '_' . implode('_', $pat));
			if( is_numeric($num) )
			{
				$pt = intval($num);
				if( $pt < 0 )
				{
					$pt = 0;
				}
				else if( $pt > $limit )
				{
					$pt = $limit;
				}
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

	/**
	 *	Show user's applications (admin)
	 */
	public function applications()
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			if( $user->admin )
			{
				return view('game/user_app', compact('user'));
			}
		}

		return redirect('/');
	}

	/**
	 *	Approve/Reject a game to public (admin)
	 */
	public function admin_pubgame(Request $request)
	{
		$result = 'fail';

		$user = User::get_current_user();
		if( $user->admin )
		{
			$game_id = $request->input('game_id');
			$pub = $request->input('pub');
			DB::transaction(function () use($game_id, $pub, &$result)
				{
					$game = Game::find($game_id);
					if( !is_null($game) )
					{
						if( $pub == 1 )
						{
							$game->is_public = 3;
							$game->update();
							$result = 'success';
						}
						else if( $pub == 0 )
						{
							$game->is_public = 2;
							$game->update();
							$result = 'success';
						}
					}
				} );
		}

		return response()->json(['result' => $result]);
	}
}
