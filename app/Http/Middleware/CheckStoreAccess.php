<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admin has access to all stores
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if request contains store_id parameter
        $storeId = $request->input('store_id') ?? $request->route('store_id');

        if ($storeId && !$user->canAccessStore($storeId)) {
            return response()->json([
                'message' => 'You do not have access to this store',
            ], 403);
        }

        return $next($request);
    }
}

