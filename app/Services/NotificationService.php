<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\PeopleNotification;

class NotificationService
{
    public function admins(string $permission, array $payload): void
    {
        Admin::query()
            ->where(function ($query) use ($permission) {
                $query->where('role', 'owner')
                    ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->where('slug', $permission));
            })
            ->each(fn (Admin $admin) => $admin->notify(new PeopleNotification($payload)));
    }

    public function employee(User $employee, array $payload): void
    {
        $employee->notify(new PeopleNotification($payload));
    }

    public function manager(?User $manager, array $payload): void
    {
        $manager?->notify(new PeopleNotification($payload));
    }
}
