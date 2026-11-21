<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ServiceAuthMiddleware
{
    /**
     * Handle an incoming request for inter-service communication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('services.cbt.api_token') ?: env('CBT_API_TOKEN');

        if (empty($expectedToken)) {
            return response()->json([
                'error' => [
                    'code' => 'SERVER_CONFIG_ERROR',
                    'message' => 'CBT API service authentication token is not configured on server.',
                    'request_id' => 'req_' . Str::uuid(),
                ]
            ], 500);
        }

        // Only accept Authorization Bearer token or X-API-Key header (never query string)
        $token = $request->bearerToken() ?? $request->header('X-API-Key');

        if (! $token || ! hash_equals($expectedToken, $token)) {
            $requestId = (string) Str::uuid();
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Token API tidak valid atau tidak disertakan.',
                    'details' => [
                        'hint' => 'Sertakan Authorization: Bearer <token> atau X-API-Key header.'
                    ],
                    'request_id' => 'req_' . $requestId,
                ]
            ], 401);
        }

        return $next($request);
    }
}
