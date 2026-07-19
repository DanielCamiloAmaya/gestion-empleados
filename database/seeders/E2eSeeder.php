<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Departamento;
use App\Models\PlatformUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('E2eSeeder solo puede ejecutarse en el entorno testing.');
        }

        PlatformUser::updateOrCreate(
            ['email' => 'e2e.control@peopleos.test'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'E2E Platform Owner',
                'password' => 'Control-E2E-2026!',
                'role' => 'platform_owner',
                'status' => 'active',
                'mfa_enabled' => true,
                'mfa_secret' => 'JBSWY3DPEHPK3PXP',
                'mfa_recovery_codes' => [Hash::make('RECOV-E2E01')],
                'mfa_confirmed_at' => now(),
                'activated_at' => now(),
            ],
        );

        $department = Departamento::updateOrCreate(
            ['nombre' => 'Tecnología'],
            ['description' => 'Equipo de producto e ingeniería.', 'cost_center' => 'CC-TECH', 'is_active' => true],
        );

        $admin = Admin::updateOrCreate(
            ['email' => 'e2e.admin@peopleos.test'],
            ['name' => 'E2E Admin', 'password' => 'PeopleOS-E2E-2026!', 'role' => 'hr_admin'],
        );

        if ($role = Role::where('organization_id', $admin->organization_id)->where('slug', 'hr-admin')->first()) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }

        $owner = Admin::updateOrCreate(
            ['email' => 'e2e.owner@peopleos.test'],
            ['name' => 'E2E Tenant Owner', 'password' => 'PeopleOS-Owner-2026!', 'role' => 'owner'],
        );

        if ($role = Role::where('organization_id', $owner->organization_id)->where('slug', 'owner')->first()) {
            $owner->roles()->syncWithoutDetaching([$role->id]);
        }

        User::updateOrCreate(
            ['username' => 'e2e.employee'],
            [
                'employee_code' => 'E2E-001',
                'first_name' => 'E2E',
                'last_name' => 'Employee',
                'email' => 'e2e.employee@peopleos.test',
                'password' => 'Employee-E2E-2026!',
                'departamento_id' => $department->id,
                'job_title' => 'Software Engineer',
                'employment_status' => 'active',
                'employment_type' => 'full_time',
                'hire_date' => today()->subMonth(),
                'location' => 'Bogotá · Híbrido',
            ],
        );
    }
}
