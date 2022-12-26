<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Game;
use App\Models\Candidate;
use App\Models\Bet;

class GameController extends Controller
{
    /**
     *  Top page
     */
    public function index()
    {
        $games = Game::all();
        return view('game/index', compact('games'));
    }

    /**
     *  Edit a game (admin)
     */
    public function edit($game_id)
    {
        return view('game/edit', compact('game_id'));
    }

    /**
     *  Update a game (admin)
     */
    public function update(Request $request)
    {
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
                    $game->user_id = $user->id;
                    $game->next_update = date("Y/m/d H:i:s");
                    $game->exclusion_update = 0;
                    //Log::info('Update game: ' . $request->input('game_name'));
                    if( $game->save() )
                    {   // Update cadidates
                        $candidate_names = explode("\n", $request->input('game_candidate'));
                        $candidate_names = array_map('trim', $candidate_names);

                        $candidate_updated = array();

                        // Update existing records
                        $candidates = Candidate::where('game_id', $game->id)->get();
                        foreach( $candidates as &$candidate )
                        {
                            $index = array_search($candidate->name, $candidate_names);
                            if( $index === false )
                            {
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
                    }
                }
            );
        }
        $games = Game::all();
        return view('game/index', compact('games'));
    }

    /**
     *  Show a game
     */
    public function show($game_id)
    {
        return view('game/show', compact('game_id'));
    }

    /**
     *  Bet in a game
     */
    public function bet($game_id)
    {
        return view('game/bet', compact('game_id'));
    }

    /**
     *  Save betting info
     */
    public function save_bet(Request $request)
    {
        $user = User::where('personal_id', Cookie::get('iden_token'))->first();
        $game_id = $request->input('game_id');
        DB::transaction(function () use($request, $user, $game_id)
            {
                $candidates = Candidate::where('game_id', $game_id)->where('result_rank', '<', 0)->select('id')->get();
                $last_bets = intval( Bet::where('game_id', $game_id)->where('user_id', $user->id)->where('payed', 0)->sum('points') );

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
                    ->select('id', 'points', 'candidate_id0')->get();
                foreach( $candidates as &$candidate )
                {
                    $bet_points = $request->input('bet_win_' . $candidate->id);
                    $found = false;
                    foreach( $bets as &$bet )
                    {
                        if( $bet->candidate_id0 == $candidate->id )
                        {
                            if( $bet_points > 0 )
                            {
                                $bet->points = $bet_points;
                                $bet->update();
                            }
                            else
                            {
                                $bet->delete();
                            }
                            $found = true;
                            break;
                        }
                    }
                    if( !$found && $bet_points > 0 )
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

                // Request to update odds
                $game = Game::find($game_id);
                $game->exclusion_update = 0;
                $game->update();
            }
        );
        return view('game/show', compact('game_id'));
    }
}
