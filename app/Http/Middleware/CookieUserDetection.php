<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

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
        }
        else
        {
            $iden_token = $this->generate_token();
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
}
