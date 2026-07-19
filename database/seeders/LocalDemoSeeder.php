<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Departamento;
use App\Models\PlatformUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('LocalDemoSeeder solo puede ejecutarse en el entorno local.');
        }

        PlatformUser::updateOrCreate(
            ['email' => 'control@peopleos.local'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Daniel Amaya',
                'password' => 'Control-PeopleOS-2026!',
                'role' => 'platform_owner',
                'status' => 'active',
                'activated_at' => now(),
            ],
        );

        $department = Departamento::where('nombre', 'Tecnología')->firstOrFail();
        $people = Departamento::where('nombre', 'Recursos Humanos')->firstOrFail();

        $owner = Admin::updateOrCreate(
            ['email' => 'admin@peopleos.local'],
            ['name' => 'Daniel Amaya', 'password' => 'PeopleOS-Demo-2026!', 'role' => 'owner'],
        );
        if ($role = Role::where('organization_id', $owner->organization_id)->where('slug', 'owner')->first()) {
            $owner->roles()->syncWithoutDetaching([$role->id]);
        }

        $manager = User::updateOrCreate(
            ['username' => 'lider.demo'],
            [
                'employee_code' => 'DEMO-001', 'first_name' => 'Mariana', 'last_name' => 'López',
                'email' => 'mariana@peopleos.local', 'password' => 'Employee-Demo-2026!',
                'departamento_id' => $people->id, 'job_title' => 'People Operations Lead',
                'employment_status' => 'active', 'employment_type' => 'full_time',
                'hire_date' => today()->subYears(2), 'location' => 'Bogotá · Híbrido',
            ],
        );

        User::updateOrCreate(
            ['username' => 'empleado.demo'],
            [
                'employee_code' => 'DEMO-002', 'first_name' => 'Santiago', 'last_name' => 'Rojas',
                'email' => 'santiago@peopleos.local', 'password' => 'Employee-Demo-2026!',
                'departamento_id' => $department->id, 'manager_id' => $manager->id,
                'job_title' => 'Product Engineer', 'employment_status' => 'active',
                'employment_type' => 'full_time', 'hire_date' => today()->subMonths(8),
                'location' => 'Medellín · Remoto',
            ],
        );
    }
}
