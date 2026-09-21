<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * GET /api/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('role')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->when($request->role_id, fn ($q, $roleId) => $q->where('role_id', $roleId))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc');

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * POST /api/admin/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
        ], 201);
    }

    /**
     * GET /api/admin/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('role'));
    }

    /**
     * PUT /api/admin/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * DELETE /api/admin/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $this->userService->deleteUser($user);

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * PATCH /api/admin/users/{user}/toggle-status
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        // Prevent disabling self
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot disable your own account.',
            ], 422);
        }

        $user = $this->userService->toggleStatus($user);

        return response()->json([
            'message' => "User status changed to: {$user->status}",
            'user' => $user->load('role'),
        ]);
    }

    /**
     * POST /api/admin/users/{user}/reset-password
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->userService->resetPassword($user, $request->password);

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }
}
