<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
//use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if ($bearerToken !== config('tier4.api_key')) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
