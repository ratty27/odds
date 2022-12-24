<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;

class GameController extends Controller
{
    public function index()
    {
        $games = Game::all();
        return view('game/index', compact('games'));
    }

    //
    public function show($game_id)
    {
        $games = Game::all();
        return view('game/show', compact('games'));
    }
}
