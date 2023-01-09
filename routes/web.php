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

// Top
Route::get('/', 'App\Http\Controllers\GameController@index');

// Admin
Route::get('/edit/{game_id}', 'App\Http\Controllers\GameController@edit');
Route::post('/update', 'App\Http\Controllers\GameController@update');
Route::get('/delete/{game_id}', 'App\Http\Controllers\GameController@delete_game');
Route::get('/close/{game_id}', 'App\Http\Controllers\GameController@close');
Route::get('/reopen/{game_id}', 'App\Http\Controllers\GameController@reopen');
Route::get('/result/{game_id}', 'App\Http\Controllers\GameController@result');
Route::post('/finish', 'App\Http\Controllers\GameController@finish');

// User's
Route::get('/login/{token}', 'App\Http\Controllers\UserController@login');
Route::get('/game/{game_id}', 'App\Http\Controllers\GameController@show');
Route::get('/bet/{game_id}', 'App\Http\Controllers\GameController@bet');
Route::post('/bet', 'App\Http\Controllers\GameController@save_bet');
Route::get('/reset_user', 'App\Http\Controllers\UserController@reset_user');
Route::post('/user_info', 'App\Http\Controllers\UserController@user_info');

// misc
Route::get('/error/{errcode}', 'App\Http\Controllers\GameController@error');
