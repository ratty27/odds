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
use App\Mail\ResetPasswordMail;

class UserController extends Controller
{
	/**
	 *  Login
	 */
	public function login($token)
	{
		DB::transaction(function () use($token)
			{
				$login = Login::where('token', $token)->select('id')->get();
				if( count($login) > 0 )
				{
					$login[0]->delete();
					User::register_user($token, config('odds.initial_points'));
					Cookie::queue('iden_token', $token, config('odds.cookie_expires'));
				}
			} );
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
				if( User::where('email', $user->email)->where('authorized', 3)->exists() )
				{
					$message = __('odds.user_email_exists');
					return;
				}
				$pass = $request->input('info_pass');
				if( strlen($pass) >= 8 )
				{
					$user->token = User::make_hash( $pass );
					$user->temp = hash('sha256', uniqid(config('app.key')) . random_int(1000000, 9999999) . 'temp');
					$user->authorized = 1;
					if( $user->save() )
					{
						$message = __("odds.info_confirm_email");
					}
					else
					{
						$message = __("odds.internal_error");
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
				$user->name = trim( $request->input('info_name') );
				if( is_null($user->name) )
				{
					$user->name = '';
				}

				// Update email address
				$email = trim( $request->input('info_email') );
				if( $user->email !== $email )
				{
					if( User::where('email', $user->email)->where('authorized', 3)->exists() )
					{
						$message = __('odds.user_email_exists');
						return;
					}
					$user->email = $email;
					$user->authorized = 0;
				}
				// Generate a temporary token, if email isn't authorized.
				if( $user->authorized == 0 )
				{
					$user->temp = $user->make_temp_token();
					$user->authorized = 1;		// Ready to send an authorize mail
				}

				if( $user->update() )
				{
					if( $user->authorized == 1 )
					{
						$message = __("odds.info_confirm_email");
					}
					else
					{
						$message = __("odds.user_info_updated");
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

		$success = false;
		DB::transaction(function () use($request, &$success, &$message)
			{
				$temp = $request->input('t');
				$user = User::where('temp', $temp)->first();
				if( !is_null($user) )
				{
					if( $user->authorized == 2 )
					{
						if( User::where('email', $user->email)->where('authorized', 3)->exists() )
						{
							$message = __('odds.user_email_exists');
							return;
						}

						$user->temp = null;
						$user->authorized = 3;
						if( $user->update() )
						{
							Cookie::queue('iden_token', $user->personal_id, 60*24*365*2);
							$message = __('odds.email_confirm_success');
							$success = true;
						}
					}
				}
			} );

		if( $success )
		{
			return view('auth/user_info', compact('message'));
		}
		return response($message, 500)->header('Content-Type', 'text/plain');
	}

	/**
	 *	Show change password page
	 */
	public function	change_password()
	{
		if( !User::is_valid_user() )
		{
			return redirect('/');
		}

		$user = User::get_current_user();
		return view('auth/change_password', compact('user'));
	}

	/**
	 *	Update password
	 */
	public function update_password(Request $request)
	{
		if( !User::is_valid_user() )
		{
			return redirect('/');
		}

		$user = User::get_current_user();

		$message = '';
		DB::transaction(function () use($request, $user, &$message)
			{
				$token = User::make_hash( $request->input('info_pass') );
				if( $user->token === $token )
				{
					$pass = $request->input('info_new_pass');
					if( strlen($pass) >= 8 )
					{
						$user->token = User::make_hash( $pass );
						if( $user->update() )
						{
							$message = __('odds.user_password_updated');
						}
						else
						{
							$message = __('odds.internal_error');
						}
					}
					else
					{
						$message = __('odds.internal_error');
					}
				}
				else
				{
					$message = __('odds.user_incorrent_password');
				}
			} );
		return view('auth/user_info', compact('message'));
	}

	/**
	 *	Show signin page
	 */
	public function user_signin()
	{
		$message = '';
		return view('auth/signin', compact('message'));
	}

	/**
	 *	Signin
	 */
	public function signin(Request $request)
	{
		$user = User::get_current_user();

		$email = trim( $request->input('info_email') );
		$exists_user = User::where('email', $email)->where('authorized', 3)->first();
		if( !is_null($exists_user) )
		{
			$pass = User::make_hash( $request->input('info_pass') );
			if( $exists_user->token === $pass )
			{
				Cookie::queue('iden_token', $exists_user->personal_id, 60*24*365*2);
				if( !is_null($user) )
				{
					if( $user->id != $exists_user->id
					 && $user->authorized < 3 )
					{
						Bet::where('user_id', $user->id)->delete();
						Game::where('user_id', $user->id)->delete();
						$user->delete();
					}
				}
				return redirect('/');
			}
		}

		$message = __("odds.user_incorrent_emailpassword");
		return view('auth/signin', compact('message'));
	}

	/**
	 *	Show forget password page
	 */
	public function reset_password()
	{
		$message = '';
		return view('auth/reset_password', compact('message'));
	}

	/**
	 *	Send an email for reset password
	 */
	public function send_reset_password(Request $request)
	{
		$message = '';
		DB::transaction(function () use($request, &$message)
			{
				$email = trim( $request->input('info_email') );
				$exists_user = User::where('email', $email)->where('authorized', 3)->first();
				if( !is_null($exists_user) )
				{
					$exists_user->temp = $exists_user->make_temp_token();
					$exists_user->temp_limit = date('Y/m/d H:i:s');
					if( $exists_user->update() )
					{
						Mail::to($exists_user->email)->send(new ResetPasswordMail($exists_user->temp));
						$message = __("odds.email_sent");
					}
					else
					{
						$message = __("odds.internal_error");
					}
				}
				else
				{
					$message = __("odds.email_incorrect");
				}
			} );
		return view('auth/reset_password', compact('message'));
	}

	/**
	 *	Request to reset password by email
	 */
	public function reset_password_email(Request $request)
	{
		$temp = $request->input('t');
		$user = User::where('temp', $temp)->where('authorized', 3)->first();
		if( !is_null($user) )
		{
			$limit = strtotime($user->temp_limit) + (60 * 60);	// 1 hour
			$now = time();
			if( $now < $limit )
			{
				$message = '';
				return view('auth/input_password', compact('user', 'message'));
			}
		}
		return redirect('/');
	}

	/**
	 *	Input new password to reset
	 */
	public function input_password(Request $request)
	{
		$message = '';

		$temp = $request->input('token');
		$user = User::where('temp', $temp)->where('authorized', 3)->first();
		if( !is_null($user) )
		{
			$pass = $request->input('info_pass');
			if( strlen($pass) >= 8 )
			{
				$user->token = User::make_hash( $pass );
				$user->temp = null;
				if( $user->update() )
				{
					Cookie::queue('iden_token', $user->personal_id, 60*24*365*2);
					$message = __("odds.user_password_updated");
					return view('auth/user_info', compact('message'));
				}
				else
				{
					$message = __("odds.internal_error");
				}
			}
			else
			{
				$message = __("odds.internal_error");
			}
		}
		return response($message, 500)->header('Content-Type', 'text/plain');
	}

	/**
	 *	Delete user registration info
	 */
	public function delete_user_info()
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			$user->email = null;
			$user->token = null;
			$user->temp = null;
			$user->authorized = 0;
			$user->update();
		}
		return redirect('/');
	}
}
