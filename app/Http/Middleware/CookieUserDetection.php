<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class CookieUserDetection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if( Cookie::has('iden_token') )
        {
            $iden_token = Cookie::get('iden_token');
            //Log::info('Receive iden_token: ' . $iden_token);
            if( !User::exists_user($iden_token) )
            {
                $iden_token = $this->create_user();
                Cookie::queue('iden_token', $iden_token, 60*24*365*2);
            }
        }
        else
        {
            $iden_token = $this->create_user();
            Cookie::queue('iden_token', $iden_token, 60*24*365*2);
        }
        return $next($request);
    }

    /**
     *  Generate unique identify token
     */
    protected function generate_token()
    {
        // Todo: Generate w/ secure random, if php8.2 later.
        return hash('sha256', uniqid(config('app.key')) . random_int(1000000, 9999999));
    }

    /**
     *  Create new user
     */
    protected function create_user()
    {
        while( true )
        {
            $iden_token = $this->generate_token();
            if( User::register_user($iden_token, config('odds.initial_points')) )
            {
                break;
            }
        }
        return $iden_token;
    }
}
