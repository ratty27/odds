<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*
Route::get('/', function () {
    return view('welcome');
});
*/

Route::get('/', 'App\Http\Controllers\GameController@index');
Route::get('/edit/{game_id}', 'App\Http\Controllers\GameController@edit');
Route::post('/update', 'App\Http\Controllers\GameController@update');
Route::get('/game/{game_id}', 'App\Http\Controllers\GameController@show');
