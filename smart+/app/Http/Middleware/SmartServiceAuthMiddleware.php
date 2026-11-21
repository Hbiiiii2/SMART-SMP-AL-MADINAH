<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SmartServiceAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = env('SMART_PLUS_API_TOKEN', 'smart_secret_token_almadinah_2026');
        
        $token = $request->bearerToken() ?? $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$token || !hash_equals($expectedToken, $token)) {
            $requestId = (string) Str::uuid();
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Token API SMART+ tidak valid atau tidak disertakan.',
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
