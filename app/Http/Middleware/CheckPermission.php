<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!$request->user()) {
            return response()->json([
                'status' => false,
                'message' => 'Maaf, Anda harus login terlebih dahulu untuk mengakses halaman ini'
            ], 401);
        }

        if (!$request->user()->hasPermissionTo($permission)) {
            return response()->json([
                'status' => false,
                'message' => 'Maaf, Anda tidak memiliki akses untuk fitur ini. Silakan hubungi administrator jika Anda memerlukan akses.'
            ], 403);
        }

        return $next($request);
    }
}
