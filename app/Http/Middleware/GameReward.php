<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;

class GameReward
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
            $user = User::where('personal_id', $iden_token)->get();
            if( count($user) > 0 )
            {
                $user[0]->ReceiveRewards();
            }
        }
        return $next($request);
    }
}
