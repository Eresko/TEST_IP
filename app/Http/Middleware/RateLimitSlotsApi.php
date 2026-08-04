<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitSlotsApi
{
    public function handle(Request $request, Closure $next): Response
    {

        $key = 'slots-api:' . $request->method() . ':' . $request->path() . ':' . $request->ip();

      
        $maxAttempts = $request->isMethod('GET') ? 100 : 30;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Слишком много запросов. Пожалуйста, подождите.',
                'retry_after_seconds' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }
}

