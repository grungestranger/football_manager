<?php

namespace App\Http\Middleware;

use Closure;

use DB;
use Cache;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($user = auth()->user()) {
            // last active time
            $user->last_active_at = DB::raw('now()');
            $user->save();

            if (!$request->ajax() && !$request->wantsJson()) {
                // challenges
                $challenges = Cache::get('challenges:' . $user->id, []);
                foreach ($challenges as $k => $v) {
                    if (!Cache::has('waiting:' . $v . ':' . $user->id)) {
                        unset($challenges[$k]);
                    }
                }

                view()->share('jwt', JWTAuth::fromUser($user));
            }
        }

        return $next($request);
    }
}
