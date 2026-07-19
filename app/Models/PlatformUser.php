<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlatformUser extends Authenticatable
{
    use Notifiable;

    protected $attributes = [
        'auth_version' => 1,
    ];

    public const PERMISSIONS = [
        'platform_owner' => ['*'],
        'operations' => ['organizations.view', 'organizations.manage', 'legal_entities.manage', 'invitations.manage', 'support.manage'],
        'support' => ['organizations.view', 'support.manage'],
        'security' => ['organizations.view', 'domains.manage', 'support.manage', 'audit.view', 'platform_users.manage'],
        'billing' => ['organizations.view', 'subscriptions.manage'],
        'auditor' => ['organizations.view', 'audit.view'],
    ];

    protected $fillable = [
        'uuid', 'name', 'email', 'password', 'role', 'status', 'mfa_enabled',
        'mfa_secret', 'mfa_recovery_codes', 'mfa_confirmed_at',
        'invitation_token_hash', 'invitation_expires_at', 'activated_at',
        'last_login_at', 'disabled_at',
        'auth_version',
    ];

    protected $hidden = [
        'password', 'remember_token', 'mfa_secret', 'mfa_recovery_codes', 'invitation_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'mfa_enabled' => 'boolean',
            'mfa_secret' => 'encrypted',
            'mfa_recovery_codes' => 'array',
            'mfa_confirmed_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'last_login_at' => 'datetime',
            'disabled_at' => 'datetime',
            'auth_version' => 'integer',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = self::PERMISSIONS[$this->role] ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->disabled_at === null;
    }
}
