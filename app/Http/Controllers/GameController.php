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
				return view('game/edit', compact('game_id'));
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
						$game_id = $request->input('game_id');
						// Update a game info.
						if( $game_id === 'new' )
						{
							$game = new Game;
						}
						else
						{
							$game = Game::find($game_id);
							if( $game->user_id != $user->id )
							{
								return;
							}
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

						$game->enabled = 1;	// 'win' is awlways enabled
						$enabled = $request->input('enabled');
						if( $enabled != null )
						{
							foreach( $enabled as $enabled_index )
							{
								$index = intval( $enabled_index );
								$game->enabled |= 1 << $index;
							}
						}

						$pubset = intval( $request->input('game_pubsetting') );
						if( $pubset == 0 )
						{	// private
							$game->is_public = 0;
						}
						else
						{	// public
							if( $game->is_public == 0 )
							{	// Apply to public
								$game->is_public = 1;
							}
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
		}
		return redirect('/mygames');
	}

	/**
	 *  Delete a game
	 */
	public function delete_game($game_id)
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			if( $user->CanEditGame() )
			{
				DB::transaction(function () use($game_id, $user)
					{
						$game = Game::find($game_id);
						if( !is_null($game) )
						{
							if( $game->user_id == $user->id )
							{
								Bet::where('game_id', $game_id)->delete();
								Odd::where('game_id', $game_id)->delete();
								Candidate::where('game_id', $game_id)->delete();
								$game->delete();
							}
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
		if( !is_null($user) )
		{
			if( $user->CanEditGame() )
			{
				DB::transaction(function () use($game_id, $user)
					{
						$game = Game::find($game_id);
						if( $game->user_id == $user->id )
						{
							if( $game->status == 0 )
							{
								$game->status = 1;
								$game->update_odds();
							}
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
		if( !is_null($user) )
		{
			if( $user->CanEditGame() )
			{
				DB::transaction(function () use($game_id, $user)
					{
						$game = Game::find($game_id);
						if( $game->user_id == $user->id )
						{
							if( $game->status == 1 )
							{
								$game->status = 0;
								$game->update();
							}
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
		if( !is_null($user) )
		{
			if( $user->CanEditGame() )
			{
				$game = Game::find($game_id);
				if( $game->user_id == $user->id )
				{
					return view('game/result', compact('game_id'));
				}
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
			if( $user->CanEditGame() )
			{
				DB::transaction(function () use($request, $user)
					{
						$game_id = $request->input('game_id');
						$game = Game::find($game_id);
						if( $game->user_id == $user->id )
						{
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
						}
					});
			}
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
				foreach( $candidates as &$candidate )
				{
					$num = $request->input('bet_win_' . $candidate->id);
					if( is_numeric($num) )
					{
						$request_bets += intval($num);
					}
				}
				// - for quinella
				if( $game->is_enabled(Bet::TYPE_QUINELLA) )
				{
					for( $i = 0; $i < count($candidates) - 1; ++$i )
					{
						for( $j = $i + 1; $j < count($candidates); ++$j )
						{
							$id0 = $candidates[$i]->id;
							$id1 = $candidates[$j]->id;
							$num = $request->input('bet_quinella_' . $id0 . '_' . $id1);
							if( is_numeric($num) )
							{
								$request_bets += intval($num);
							}
						}
					}
				}
				// - for exacta
				if( $game->is_enabled(Bet::TYPE_EXACTA) )
				{
					for( $i = 0; $i < count($candidates); ++$i )
					{
						for( $j = 0; $j < count($candidates); ++$j )
						{
							if( $i == $j ) continue;
							$id0 = $candidates[$i]->id;
							$id1 = $candidates[$j]->id;
							$num = $request->input('bet_exacta_' . $id0 . '_' . $id1);
							if( is_numeric($num) )
							{
								$request_bets += intval($num);
							}
						}
					}
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
				$bets = Bet::where('game_id', $game_id)
					->where('user_id', $user->id)
					->where('type', Bet::TYPE_WIN)
					->where('payed', 0)
					->orderBy('candidate_id0', 'asc')
					->select('id', 'points', 'candidate_id0')->get();
				$beti = 0;
				foreach( $candidates as &$candidate )
				{
					$bet_points = $request->input('bet_win_' . $candidate->id);
					if( !is_numeric($bet_points) )
					{
						$bet_points = 0;
					}
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
						$bet->type = Bet::TYPE_WIN;
						$bet->game_id = $game_id;
						$bet->user_id = $user->id;
						$bet->candidate_id0 = $candidate->id;
						$bet->points = $bet_points;
						$bet->payed = 0;
						$bet->save();
					}
				}
				// - for quinella
				if( $game->is_enabled(Bet::TYPE_QUINELLA) )
				{
					$bets = Bet::where('game_id', $game_id)
						->where('user_id', $user->id)
						->where('type', Bet::TYPE_QUINELLA)
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
							if( !is_numeric($bet_points) )
							{
								$bet_points = 0;
							}
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
								$bet->type = Bet::TYPE_QUINELLA;
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
				// - for exacta
				if( $game->is_enabled(Bet::TYPE_EXACTA) )
				{
					$bets = Bet::where('game_id', $game_id)
						->where('user_id', $user->id)
						->where('type', Bet::TYPE_EXACTA)
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
							if( !is_numeric($bet_points) )
							{
								$bet_points = 0;
							}
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
								$bet->type = Bet::TYPE_EXACTA;
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
