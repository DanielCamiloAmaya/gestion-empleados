<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('INITIAL_PLATFORM_OWNER_EMAIL');
        $password = env('INITIAL_PLATFORM_OWNER_PASSWORD');
        if (blank($email) || blank($password)) {
            $this->command?->warn('Propietario de plataforma omitido: configura INITIAL_PLATFORM_OWNER_EMAIL e INITIAL_PLATFORM_OWNER_PASSWORD.');

            return;
        }

        PlatformUser::updateOrCreate(
            ['email' => Str::lower($email)],
            [
                'uuid' => (string) Str::uuid(),
                'name' => env('INITIAL_PLATFORM_OWNER_NAME', 'PeopleOS Platform Owner'),
                'password' => $password,
                'role' => 'platform_owner',
                'status' => 'active',
                'activated_at' => now(),
            ],
        );
    }
}
