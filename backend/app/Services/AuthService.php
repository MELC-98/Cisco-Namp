<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Attempt to authenticate a user and issue a Sanctum token.
     *
     * @return array{user: User, token: string}|null
     */
    public function login(string $email, string $password, bool $remember = false): ?array
    {
        $user = User::where('email', $email)->first();

        // Validate credentials
        if (!$user || !Hash::check($password, $user->password)) {
            $this->auditService->log(
                action: AuditAction::LOGIN_FAILED,
                userId: $user?->id,
                description: "Failed login attempt for: {$email}",
                status: 'failed',
                request: request()
            );
            return null;
        }

        // Check if account is active
        if (!$user->isActive()) {
            $this->auditService->log(
                action: AuditAction::LOGIN_FAILED,
                userId: $user->id,
                description: "Login attempt on disabled account: {$email}",
                status: 'failed',
                request: request()
            );
            return null;
        }

        // Token expiry: 8 hours default, 30 days with "remember me"
        $expiration = $remember ? now()->addDays(30) : now()->addHours(8);

        $token = $user->createToken(
            'auth-token',
            ['*'],
            $expiration
        )->plainTextToken;

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        // Audit log
        $this->auditService->log(
            action: AuditAction::LOGIN,
            userId: $user->id,
            description: "User logged in: {$user->email}",
            request: request()
        );

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoke the current user's token and log the action.
     */
    public function logout(User $user): void
    {
        // Revoke the current access token
        $user->currentAccessToken()?->delete();

        $this->auditService->logAction(
            action: AuditAction::LOGOUT,
            description: "User logged out: {$user->email}"
        );
    }
}
