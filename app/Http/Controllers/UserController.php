<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Login;
use App\Models\User;
use App\Models\Bet;

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
                // Todo: Record to log
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

    }
}
