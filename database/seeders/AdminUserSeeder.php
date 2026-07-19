<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Administrador inicial omitido: configura INITIAL_ADMIN_EMAIL e INITIAL_ADMIN_PASSWORD.');

            return;
        }

        $admin = Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('INITIAL_ADMIN_NAME', 'PeopleOS Owner'),
                'password' => $password,
                'role' => 'owner',
            ]
        );

        $roleSlug = $admin->role === 'owner' ? 'owner' : 'hr-admin';
        if ($role = Role::where('organization_id', $admin->organization_id)->where('slug', $roleSlug)->first()) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
