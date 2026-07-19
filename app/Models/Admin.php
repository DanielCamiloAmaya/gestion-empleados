<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use BelongsToOrganization, HasFactory, Notifiable;

    protected $attributes = [
        'status' => 'active',
        'auth_version' => 1,
    ];

    protected $fillable = [
        'organization_id', 'name', 'email', 'password', 'role', 'status', 'last_login_at', 'disabled_at', 'auth_version',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'mfa_enabled' => 'boolean',
            'mfa_secret' => 'encrypted',
            'mfa_recovery_codes' => 'array',
            'mfa_confirmed_at' => 'datetime',
            'disabled_at' => 'datetime',
            'auth_version' => 'integer',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner' || $this->roles()->where('slug', 'owner')->exists()) {
            return true;
        }

        if (! $this->roles()->exists() && $this->role === 'hr_admin') {
            return ! in_array($permission, ['security.manage', 'integrations.manage'], true);
        }

        return $this->roles()->whereHas('permissions', fn ($query) => $query->where('slug', $permission))->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return collect($permissions)->contains(fn ($permission) => $this->hasPermission($permission));
    }
}
