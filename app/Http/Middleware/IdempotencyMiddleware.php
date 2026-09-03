<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return $next($request);
        }

        $redisKey = "idempotency:{$idempotencyKey}";

        if ($cachedResponse = Cache::get($redisKey)) {
            return response()->json($cachedResponse['body'], $cachedResponse['status']);
        }

        $lock = Cache::lock("idempotency_lock:{$idempotencyKey}", 10);

        if (!$lock->get()) {
            return response()->json([
                'error' => 'Запрос уже обрабатывается. Пожалуйста, подождите.'
            ], 409);
        }

        try {

            if ($cachedResponse = Cache::get($redisKey)) {
                return response()->json($cachedResponse['body'], $cachedResponse['status']);
            }

            $response = $next($request);


            if ($response->isSuccessful()) {
                $responseData = $response instanceof JsonResponse
                    ? $response->getData(true)
                    : json_decode($response->getContent(), true);
                Cache::put($redisKey, [
                    'status' => $response->getStatusCode(),
                    'body'   => $responseData
                ], now()->addSeconds(15)); 
            }

            return $response;

        } finally {
            $lock->release();
        }
    }
}
