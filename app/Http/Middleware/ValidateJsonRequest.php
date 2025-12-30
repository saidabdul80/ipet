<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJsonRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate POST, PUT, PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            // Check if Content-Type is application/json
            if (!$request->isJson()) {
                return response()->json([
                    'message' => 'Content-Type must be application/json',
                ], 415);
            }

            // Validate JSON is valid
            if ($request->getContent() && json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'message' => 'Invalid JSON format',
                    'error' => json_last_error_msg(),
                ], 400);
            }
        }

        return $next($request);
    }
}

