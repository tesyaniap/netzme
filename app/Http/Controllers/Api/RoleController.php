<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponse;

    /**
     * Semua Permission Role
     */
    public function index()
    {
        $roles = Role::where('guard_name', 'api')
            ->with('permissions')
            ->get()
            ->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->map(function($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'description' => $permission->description ?? null,
                        ];
                    }),
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at
                ];
            });

        return $this->successResponse($roles, 'Roles retrieved successfully');
    }

    /**
     * Role Detail
     */
    public function show($id)
    {
        $role = Role::where('guard_name', 'api')->with('permissions')->find($id);

        if (!$role) {
            return $this->errorResponse('Role not found', null, 404);
        }

        return $this->successResponse([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')
        ], 'Role retrieved successfully');
    }

    /**
     * Buat Role
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api'
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->successResponse([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')
        ], 'Role created successfully', 201);
    }

    /**
     * Update Role
     */
    public function update(Request $request, $id)
    {
        $role = Role::where('guard_name', 'api')->find($id);

        if (!$role) {
            return $this->errorResponse('Role not found', null, 404);
        }

        $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if ($request->has('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->successResponse([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')
        ], 'Role updated successfully');
    }

    /**
     *  assign permissions to role
     */
    public function assignPermissions(Request $request, $id)
    {
        $role = Role::where('guard_name', 'api')->find($id);

        if (!$role) {
            return $this->errorResponse('Role not found', null, 404);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        $role->syncPermissions($request->permissions);

        return $this->successResponse([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description ?? null,
                ];
            })
        ], 'Permissions assigned successfully');
    }

    /**
     *  semua Permissions yang tersedia
     */
    public function permissions()
    {
        $permissions = Permission::where('guard_name', 'api')
            ->get()
            ->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description ?? null,
                ];
            });

        return $this->successResponse($permissions, 'Permissions retrieved successfully');
    }
}
