<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Game;
use App\Models\Candidate;

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
        $user = User::where('personal_id', Cookie::get('iden_token'))->take(1)->get()[0];
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
                    Log::info('Update game: ' . $request->input('game_name'));
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
        $games = Game::all();
        return view('game/show', compact('games'));
    }
}
