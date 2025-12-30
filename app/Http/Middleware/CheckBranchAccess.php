<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admin has access to all branches
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if request contains branch_id parameter
        $branchId = $request->input('branch_id') ?? $request->route('branch_id');

        if ($branchId && !$user->canAccessBranch($branchId)) {
            return response()->json([
                'message' => 'You do not have access to this branch',
            ], 403);
        }

        return $next($request);
    }
}

