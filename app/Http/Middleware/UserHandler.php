<?php

namespace App\Http\Middleware;

use Closure;

use Cache;
use JWTAuth;

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
