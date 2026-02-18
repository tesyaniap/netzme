<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login admin / mitra
     */
    public function login(LoginRequest $request)
    {
        $user = User::with(['roles', 'mitra'])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid credentials', null, 401);
        }

        if ($user->status !== 'active') {
            return $this->errorResponse('Account is inactive', null, 403);
        }

        // Validasi role
        if (!$user->hasAnyRole(['admin', 'mitra'])) {
            return $this->errorResponse('Unauthorized role', null, 403);
        }

        $token = $user->createToken('auth_token')->accessToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 'Login successful');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        
        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * Refresh Token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        $request->user()->token()->revoke();
        
        $token = $user->createToken('auth_token')->accessToken;

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 'Token refreshed successfully');
    }

    /**
     * Get User active
     */
    public function me(Request $request)
    {
        $user = User::with(['roles', 'mitra'])->find($request->user()->id);

        return $this->successResponse(
            new UserResource($user),
            'User data retrieved successfully'
        );
    }

    /**
     * Get User Permissions
     */
    public function permissions(Request $request)
    {
        $user = $request->user();
        $user->load('roles');
        
        $permissions = [
            'role' => $user->getRoleNames()->first(),
            'permissions' => $this->getUserPermissions($user)
        ];

        return $this->successResponse(
            $permissions,
            'Permissions retrieved successfully'
        );
    }

    /**
     * Get permissions berdasar role
     */
    private function getUserPermissions($user)
    {
        // permission spatie
        return $user->getAllPermissions()->pluck('name')->toArray();
    }
}
