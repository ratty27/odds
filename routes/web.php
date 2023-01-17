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
//Route::get('/', 'App\Http\Controllers\GameController@index');
Route::get('/', 'App\Http\Controllers\PortalController@index');

// Admin
Route::get('/admin_app', 'App\Http\Controllers\GameController@applications');
Route::post('/admin_pubgame', 'App\Http\Controllers\GameController@admin_pubgame');

// User
// - standard
Route::get('/login/{token}', 'App\Http\Controllers\UserController@login');
Route::get('/game/{game_id}', 'App\Http\Controllers\GameController@show');
Route::get('/bet/{game_id}', 'App\Http\Controllers\GameController@bet');
Route::post('/bet', 'App\Http\Controllers\GameController@save_bet');
Route::get('/reset_user', 'App\Http\Controllers\UserController@reset_user');
// - authorization
Route::get('/user_info', 'App\Http\Controllers\UserController@user_info');
Route::post('/register_user', 'App\Http\Controllers\UserController@register_user');
Route::post('/update_user', 'App\Http\Controllers\UserController@update_user');
Route::get('/authorize_email', 'App\Http\Controllers\UserController@authorize_email');
Route::get('/change_password', 'App\Http\Controllers\UserController@change_password');
Route::post('/change_password', 'App\Http\Controllers\UserController@update_password');
Route::get('/user_signin', 'App\Http\Controllers\UserController@user_signin');
Route::post('/user_signin', 'App\Http\Controllers\UserController@signin');
Route::get('/user_reset_password', 'App\Http\Controllers\UserController@reset_password');
Route::post('/user_reset_password', 'App\Http\Controllers\UserController@send_reset_password');
Route::get('/reset_password_email', 'App\Http\Controllers\UserController@reset_password_email');
Route::post('/input_password', 'App\Http\Controllers\UserController@input_password');
Route::get('/delete_user_info', 'App\Http\Controllers\UserController@delete_user_info');
// - management game
Route::get('/edit/{game_id}', 'App\Http\Controllers\GameController@edit');
Route::post('/update', 'App\Http\Controllers\GameController@update');
Route::get('/delete/{game_id}', 'App\Http\Controllers\GameController@delete_game');
Route::get('/close/{game_id}', 'App\Http\Controllers\GameController@close');
Route::get('/reopen/{game_id}', 'App\Http\Controllers\GameController@reopen');
Route::get('/result/{game_id}', 'App\Http\Controllers\GameController@result');
Route::post('/finish', 'App\Http\Controllers\GameController@finish');

// misc
Route::get('/error/{errcode}', 'App\Http\Controllers\GameController@error');
