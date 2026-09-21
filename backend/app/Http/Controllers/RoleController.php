<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * GET /api/admin/roles
     */
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')
            ->with('permissions')
            ->get();

        return response()->json($roles);
    }

    /**
     * GET /api/admin/roles/{role}
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json(
            $role->load('permissions')->loadCount('users')
        );
    }
}
