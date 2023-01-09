<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Login;
use App\Models\User;
use App\Models\Bet;
use App\Mail\AuthorizeMail;

class UserController extends Controller
{
	/**
	 *  Login
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
	 *  Force reset user points
	 */
	public function reset_user()
	{
		if( !User::is_valid_user() )
		{
			return User::auth_login();
		}

		$user = User::where('personal_id', Cookie::get('iden_token'))->first();
		DB::transaction(function () use($user)
			{
				Bet::where('user_id', $user->id)->where('payed', 0)->delete();
				$user->points = config('odds.initial_points');
				$user->update();
				Log::channel('oddslog')->info('RESET: ', ['id' => $user->id]);
			}
		);
		return redirect('/');
	}

	/**
	 *  User info page
	 */
	public function user_info()
	{
		if( !User::is_valid_user() )
		{
			return User::auth_login();
		}

		$message = '';
		return view('auth/user_info', compact('message'));
	}

	/**
	 *  Register user
	 */
	public function register_user(Request $request)
	{
		$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
		$user = User::where('personal_id', $user_token)->first();
		if( is_null($user) )
		{
			return User::auth_login();
		}

		$message = '';
		DB::transaction(function () use($request, $user, &$message)
			{
				$user->name = $request->input('info_name');
				if( is_null($user->name) )
				{
					$user->name = '';
				}
				$user->email = $request->input('info_email');
				$user->token = User::make_hash( $request->input('info_pass') );
				$user->temp = hash('sha256', uniqid(config('app.key')) . random_int(1000000, 9999999) . 'temp');
				$user->authorized = 0;
				if( $user->save() )
				{
					Mail::to($user->email)->send(new AuthorizeMail($user->personal_id, $user->temp));
					$message = __("odds.info_confirm_email");
				}
				else
				{
					$message = __("odds.internal_error");
				}
			}
		);

		return view('auth/user_info', compact('message'));
	}
}
