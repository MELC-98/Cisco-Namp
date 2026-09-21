<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the authenticated user has a specific role or permission.
 *
 * Usage in routes:
 *   ->middleware('role:administrator')
 *   ->middleware('permission:manage_users')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $type, string $value = ''): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$user->isActive()) {
            return response()->json(['message' => 'Account is disabled'], 403);
        }

        if ($type === 'role') {
            if (!$user->role || $user->role->name !== $value) {
                // Administrators bypass role checks
                if (!$user->isAdmin()) {
                    return response()->json(['message' => 'Insufficient role'], 403);
                }
            }
        }

        if ($type === 'permission') {
            if (!$user->hasPermission($value)) {
                return response()->json(['message' => 'Insufficient permissions'], 403);
            }
        }

        return $next($request);
    }
}
