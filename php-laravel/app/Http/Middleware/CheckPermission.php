<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Redis;

class CheckPermission
{
    // php artisan make:middleware CheckPermission

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $url = $request->path();
        $releaseUrl = array(
            'login/getVerificationCode',
            'login/verification',
            'login/loginOut',
            'getPublicInfo'
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
