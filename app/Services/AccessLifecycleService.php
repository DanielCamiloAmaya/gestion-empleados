<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ApiToken;
use App\Models\EmployeeInvitation;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccessLifecycleService
{
    public function revokeEmployee(User $employee): void
    {
        $employee->forceFill([
            'auth_version' => $employee->auth_version + 1,
            'remember_token' => Str::random(60),
        ])->save();

        $this->deleteGuardSessions('web', $employee->id);
        EmployeeInvitation::where('user_id', $employee->id)->whereNull('accepted_at')->delete();
    }

    public function revokeAdmin(Admin $admin): void
    {
        $admin->forceFill([
            'auth_version' => $admin->auth_version + 1,
            'remember_token' => Str::random(60),
        ])->save();

        ApiToken::where('created_by', $admin->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $this->deleteGuardSessions('admin', $admin->id);
    }

    public function revokePlatformUser(PlatformUser $user): void
    {
        $user->forceFill([
            'auth_version' => $user->auth_version + 1,
            'remember_token' => Str::random(60),
        ])->save();

        $this->deleteGuardSessions('platform', $user->id);
    }

    private function deleteGuardSessions(string $guard, int $actorId): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        $loginKey = Auth::guard($guard)->getName();
        $sessionIds = DB::table('sessions')
            ->where('user_id', $actorId)
            ->get(['id', 'payload'])
            ->filter(function ($session) use ($loginKey, $actorId): bool {
                $decoded = base64_decode((string) $session->payload, true);
                if ($decoded === false) {
                    return false;
                }

                try {
                    $attributes = unserialize($decoded, ['allowed_classes' => false]);
                } catch (\Throwable) {
                    return false;
                }

                return is_array($attributes)
                    && (string) ($attributes[$loginKey] ?? '') === (string) $actorId;
            })
            ->pluck('id');

        if ($sessionIds->isNotEmpty()) {
            DB::table('sessions')->whereIn('id', $sessionIds)->delete();
        }
    }
}
