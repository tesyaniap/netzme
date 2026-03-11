<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RolePermission
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json([
                'status' => false,
                'message' => 'Maaf, Anda harus login terlebih dahulu untuk mengakses halaman ini'
            ], 401);
        }

        $user = $request->user();
        $userRoles = $user->getRoleNames();
        $hasRole = $user->hasAnyRole($roles);
        
        // Debug log
        \Log::info('RolePermission Debug', [
            'required_roles' => $roles,
            'user_roles' => $userRoles,
            'has_role' => $hasRole
        ]);

        if (!$hasRole) {
            return response()->json([
                'status' => false,
                'message' => 'Maaf, Anda tidak memiliki akses untuk halaman ini',
                'debug' => [
                    'required_roles' => $roles,
                    'user_roles' => $userRoles,
                    'has_role' => $hasRole
                ]
            ], 403);
        }

        return $next($request);
    }
}
