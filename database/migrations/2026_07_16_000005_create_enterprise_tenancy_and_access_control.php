<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('tax_identifier', 80)->nullable();
            $table->string('country_code', 2)->default('CO');
            $table->string('timezone', 64)->default('America/Bogota');
            $table->string('locale', 10)->default('es');
            $table->string('plan', 30)->default('enterprise');
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        $organizationId = DB::table('organizations')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'PeopleOS Demo',
            'slug' => 'peopleos-demo',
            'legal_name' => 'PeopleOS Demo',
            'country_code' => 'CO',
            'timezone' => 'America/Bogota',
            'locale' => 'es',
            'plan' => 'enterprise',
            'is_active' => true,
            'settings' => json_encode(['brand_color' => '#176B5B']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['departamentos', 'users', 'admins', 'leave_requests', 'onboarding_tasks', 'performance_goals', 'audit_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->index()->constrained('organizations')->restrictOnDelete();
            });
            DB::table($tableName)->whereNull('organization_id')->update(['organization_id' => $organizationId]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('mfa_enabled')->default(false)->index();
            $table->text('mfa_secret')->nullable();
            $table->json('mfa_recovery_codes')->nullable();
            $table->timestamp('mfa_confirmed_at')->nullable();
            $table->dropUnique('users_email_unique');
            $table->dropUnique('users_username_unique');
            $table->dropUnique('users_employee_code_unique');
            $table->unique(['organization_id', 'email']);
            $table->unique(['organization_id', 'username']);
            $table->unique(['organization_id', 'employee_code']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('mfa_enabled')->default(false)->index();
            $table->text('mfa_secret')->nullable();
            $table->json('mfa_recovery_codes')->nullable();
            $table->timestamp('mfa_confirmed_at')->nullable();
            $table->dropUnique('admins_email_unique');
            $table->unique(['organization_id', 'email']);
        });

        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropUnique('departamentos_nombre_unique');
            $table->dropUnique('departamentos_cost_center_unique');
            $table->unique(['organization_id', 'nombre']);
            $table->unique(['organization_id', 'cost_center']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module', 60)->index();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('admin_role', function (Blueprint $table) {
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['admin_id', 'role_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('employee_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        $permissions = [
            ['people.view', 'Personas', 'people', 'Consultar directorio y perfiles laborales'],
            ['people.manage', 'Gestionar personas', 'people', 'Crear, actualizar, importar y archivar empleados'],
            ['departments.manage', 'Gestionar departamentos', 'people', 'Administrar estructura organizacional'],
            ['approvals.review', 'Revisar aprobaciones', 'operations', 'Aprobar o rechazar solicitudes y entregables'],
            ['onboarding.manage', 'Gestionar onboarding', 'talent', 'Asignar y administrar onboarding'],
            ['goals.manage', 'Gestionar objetivos', 'talent', 'Crear y supervisar objetivos'],
            ['documents.manage', 'Gestionar documentos', 'documents', 'Publicar y controlar documentos laborales'],
            ['reviews.manage', 'Gestionar evaluaciones', 'talent', 'Configurar ciclos y evaluaciones'],
            ['offboarding.manage', 'Gestionar offboarding', 'operations', 'Administrar salidas y listas de control'],
            ['reports.view', 'Consultar reportes', 'analytics', 'Acceder a reportes y exportaciones'],
            ['integrations.manage', 'Gestionar integraciones', 'platform', 'Configurar API, SSO, SCIM y webhooks'],
            ['security.manage', 'Gestionar seguridad', 'governance', 'Administrar roles, controles y cumplimiento'],
            ['audit.view', 'Consultar auditoria', 'governance', 'Consultar el historial de auditoria'],
            ['talent.manage', 'Gestionar talento', 'talent', 'Administrar reclutamiento y aprendizaje'],
            ['time.manage', 'Gestionar tiempo', 'operations', 'Administrar asistencia y jornadas'],
            ['compensation.manage', 'Gestionar compensacion', 'compensation', 'Consultar y administrar datos salariales'],
        ];

        foreach ($permissions as [$slug, $name, $module, $description]) {
            DB::table('permissions')->insert(compact('slug', 'name', 'module', 'description') + ['created_at' => now(), 'updated_at' => now()]);
        }

        $ownerRole = DB::table('roles')->insertGetId([
            'organization_id' => $organizationId,
            'name' => 'Propietario',
            'slug' => 'owner',
            'description' => 'Control total y no delegable de la organizacion.',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $hrRole = DB::table('roles')->insertGetId([
            'organization_id' => $organizationId,
            'name' => 'RR. HH.',
            'slug' => 'hr-admin',
            'description' => 'Operacion integral de personas sin configuracion critica de plataforma.',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionIds = DB::table('permissions')->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => $ownerRole]);
        }
        $hrPermissionIds = DB::table('permissions')->whereNotIn('slug', ['security.manage', 'integrations.manage'])->pluck('id');
        foreach ($hrPermissionIds as $permissionId) {
            DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => $hrRole]);
        }

        DB::table('admins')->orderBy('id')->get()->each(function ($admin) use ($ownerRole, $hrRole) {
            DB::table('admin_role')->insert([
                'admin_id' => $admin->id,
                'role_id' => $admin->role === 'owner' ? $ownerRole : $hrRole,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_invitations');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('admin_role');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');

        Schema::table('leave_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('reviewed_by_user_id'));

        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'nombre']);
            $table->dropUnique(['organization_id', 'cost_center']);
            $table->unique('nombre');
            $table->unique('cost_center');
        });
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'email']);
            $table->unique('email');
            $table->dropColumn(['mfa_enabled', 'mfa_secret', 'mfa_recovery_codes', 'mfa_confirmed_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'email']);
            $table->dropUnique(['organization_id', 'username']);
            $table->dropUnique(['organization_id', 'employee_code']);
            $table->unique('email');
            $table->unique('username');
            $table->unique('employee_code');
            $table->dropColumn(['mfa_enabled', 'mfa_secret', 'mfa_recovery_codes', 'mfa_confirmed_at']);
        });

        foreach (['audit_logs', 'performance_goals', 'onboarding_tasks', 'leave_requests', 'admins', 'users', 'departamentos'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropConstrainedForeignId('organization_id'));
        }
        Schema::dropIfExists('organizations');
    }
};
