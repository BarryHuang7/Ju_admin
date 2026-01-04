<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class CheckPermission
{
    // php artisan make:middleware CheckPermission

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $url = $request->path();
        $releaseUrl = array(
            'login/getVerificationCode',
            'login/verification',
            'login/loginOut'
        );

        if (!in_array($url, $releaseUrl)) {
            $authorization = $request->header('Authorization');

            if ($authorization) {
                if (!Redis::exists($authorization)) {
                    abort(403, '无权访问');
                }
            } else {
                abort(403, '无权访问');
            }
        }

        return $next($request);
    }
}
