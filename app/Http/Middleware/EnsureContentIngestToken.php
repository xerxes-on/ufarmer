<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContentIngestToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('general.content.ingest_token');

        if (! is_string($configuredToken) || $configuredToken === '') {
            return response()->json([
                'success' => false,
                'message' => 'Content ingest token is not configured.',
            ], 403);
        }

        $providedToken = $request->bearerToken() ?: $request->header('X-Content-Ingest-Token');

        if (! is_string($providedToken) || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid content ingest token.',
            ], 401);
        }

        return $next($request);
    }
}
