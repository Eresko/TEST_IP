<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitCreateOrders
{
    public function handle($request, Closure $next)
    {

        $key = 'create-order:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => 'Превышен лимит запросов. Попробуйте позже.',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, 60); // 60 секунд

        return $next($request);
    }
}