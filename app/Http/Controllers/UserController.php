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
			return redirect('/');
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
			return redirect('/');
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
			return redirect('/');
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

	/**
	 *	Update user
	 */
	public function update_user(Request $request)
	{
		$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
		$user = User::where('personal_id', $user_token)->first();
		if( is_null($user) )
		{
			return redirect('/');
		}

		$message = '';
		DB::transaction(function () use($request, $user, &$message)
			{
				$user->name = $request->input('info_name');
				if( is_null($user->name) )
				{
					$user->name = '';
				}

				// Update email address
				$email = $request->input('info_email');
				if( $user->email != $email )
				{
					$user->email = $email;
					$user->authorized = 0;
				}
				// Generate a temporary token, if email isn't authorized.
				if( $user->authorized == 0 )
				{
					$user->temp = hash('sha256', uniqid(config('app.key')) . random_int(1000000, 9999999) . 'temp');
				}

				if( $user->update() )
				{
					if( $user->authorized == 0 )
					{
						Mail::to($user->email)->send(new AuthorizeMail($user->personal_id, $user->temp));
						$message = __("odds.info_confirm_email");
					}
				}
				else
				{
					$message = __("odds.internal_error");
				}
			}
		);

		return view('auth/user_info', compact('message'));
	}

	/**
	 *	Authorize by email
	 */
	public function authorize_email(Request $request)
	{
		$message = __('odds.email_confirm_fail');

		$temp = $request->input('t');
		$user = User::where('temp', $temp)->first();
		if( !is_null($user) )
		{
			$user->temp = null;
			$user->authorized = 1;
			if( $user->update() )
			{
				Cookie::queue('iden_token', $user->personal_id, 60*24*365*2);
				$message = __('odds.email_confirm_success');
				return view('auth/user_info', compact('message'));
			}
		}

		return response($message, 500)->header('Content-Type', 'text/plain');
	}
}
