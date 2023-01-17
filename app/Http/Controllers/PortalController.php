<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

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
					Cookie::queue('iden_token', $token, 60*24*365*2);
				} );

			$user = User::get_current_user();
			if( is_null($user) )
			{
				return response(__("odds.internal_error"), 500)->header('Content-Type', 'text/plain');
			}
		}

		return view('portal/top', compact('user'));
	}
}
