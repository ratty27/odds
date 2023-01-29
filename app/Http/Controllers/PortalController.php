<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Info;

class PortalController extends Controller
{
	/**
	 *  Top page
	 */
	public function index()
	{
		$user = User::get_current_user();
		if( is_null($user) )
		{
			if( config('odds.confirm_robot') )
			{
				return User::auth_login();
			}

			DB::transaction(function ()
				{
					$token = User::generate_token();
					User::register_user($token, config('odds.initial_points'));
					Cookie::queue('iden_token', $token, config('odds.cookie_expires'));
				} );

			$user = User::get_current_user();
			if( is_null($user) )
			{
				return response(__("odds.internal_error"), 500)->header('Content-Type', 'text/plain');
			}
		}
		else
		{
			DB::transaction(function ()
				{
					User::update_cookie(false);
				} );
		}

		return view('portal/top', compact('user'));
	}

	/**
	 *	Edit info page (admin)
	 */
	public function edit_info()
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			if( $user->admin )
			{
				return view('portal/edit_info', compact('user'));
			}
		}
		return redirect('/');
	}

	/**
	 *	Add info (admin)
	 */
	public function add_info(Request $request)
	{
		$user = User::get_current_user();
		if( !is_null($user) )
		{
			if( $user->admin )
			{
				$message = $request->input('info_message');
				if( !is_null($message) )
				{
					$message = trim($message);
					if( strlen($message) > 0 )
					{
						$info = new Info;
						$info->message = $message;
						$info->save();
					}
				}
			}
		}
		return redirect('/');
	}
}
