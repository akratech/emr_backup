<?php

namespace App\Http\Middleware;

use DB;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class AuthenticateMobileApi
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = "api") {
        if (!Auth::guard($guard)->check()) {
            $response = ['status' => '0', "status_code" => 101, 'message' => 'Invalid AuthenticateMobileApi'];
            return Response::json($response,200,[],JSON_FORCE_OBJECT);
        }
                
        return $next($request);
    }

}
