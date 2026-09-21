<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Create a new user account.
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Hashed via User model cast
            'role_id' => $data['role_id'],
            'status' => $data['status'] ?? 'active',
        ]);

        $this->auditService->logAction(
            action: AuditAction::CREATE_USER,
            objectType: 'user',
            objectId: $user->id,
            description: "Created user: {$user->email} with role ID: {$user->role_id}"
        );

        return $user->load('role');
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        $changes = [];

        if (isset($data['name']) && $data['name'] !== $user->name) {
            $changes[] = "name: {$user->name} → {$data['name']}";
        }

        if (isset($data['email']) && $data['email'] !== $user->email) {
            $changes[] = "email: {$user->email} → {$data['email']}";
        }

        if (isset($data['role_id']) && $data['role_id'] !== $user->role_id) {
            $changes[] = "role_id: {$user->role_id} → {$data['role_id']}";

            $this->auditService->logAction(
                action: AuditAction::CHANGE_ROLE,
                objectType: 'user',
                objectId: $user->id,
                description: "Changed role for {$user->email}: {$user->role_id} → {$data['role_id']}"
            );
        }

        $user->update($data);

        $this->auditService->logAction(
            action: AuditAction::UPDATE_USER,
            objectType: 'user',
            objectId: $user->id,
            description: "Updated user: {$user->email}. Changes: " . implode(', ', $changes)
        );

        return $user->load('role');
    }

    /**
     * Toggle a user's active/disabled status.
     */
    public function toggleStatus(User $user): User
    {
        $newStatus = $user->status === 'active' ? 'disabled' : 'active';
        $user->update(['status' => $newStatus]);

        $action = $newStatus === 'active' ? AuditAction::ENABLE_USER : AuditAction::DISABLE_USER;

        $this->auditService->logAction(
            action: $action,
            objectType: 'user',
            objectId: $user->id,
            description: "User {$user->email} status changed to: {$newStatus}"
        );

        return $user;
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        $user->update(['password' => $newPassword]);

        // Revoke all existing tokens
        $user->tokens()->delete();

        $this->auditService->logAction(
            action: AuditAction::RESET_PASSWORD,
            objectType: 'user',
            objectId: $user->id,
            description: "Password reset for user: {$user->email}"
        );
    }

    /**
     * Delete a user account.
     */
    public function deleteUser(User $user): void
    {
        $email = $user->email;
        $id = $user->id;

        // Revoke all tokens first
        $user->tokens()->delete();
        $user->delete();

        $this->auditService->logAction(
            action: AuditAction::DELETE_USER,
            objectType: 'user',
            objectId: $id,
            description: "Deleted user: {$email}"
        );
    }
}
