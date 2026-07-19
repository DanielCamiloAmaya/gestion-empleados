<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $t->foreignId('hiring_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title');
            $t->string('location')->nullable();
            $t->string('employment_type', 30);
            $t->text('description');
            $t->string('status', 20)->default('draft')->index();
            $t->date('closes_at')->nullable();
            $t->timestamps();
        });
        Schema::create('candidates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('email');
            $t->string('phone', 30)->nullable();
            $t->string('stage', 30)->default('applied')->index();
            $t->unsignedTinyInteger('score')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['job_posting_id', 'email']);
        });
        Schema::create('attendance_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamp('clocked_in_at')->index();
            $t->timestamp('clocked_out_at')->nullable();
            $t->string('clock_in_ip', 45)->nullable();
            $t->string('clock_out_ip', 45)->nullable();
            $t->string('source', 20)->default('web');
            $t->text('note')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'clocked_in_at']);
        });
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('provider')->nullable();
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('duration_minutes')->default(60);
            $t->boolean('is_mandatory')->default(false);
            $t->string('status', 20)->default('active')->index();
            $t->timestamps();
        });
        Schema::create('course_enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('due_date')->nullable();
            $t->string('status', 20)->default('assigned')->index();
            $t->unsignedTinyInteger('score')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['course_id', 'user_id']);
        });
        Schema::create('compensation_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->decimal('base_salary', 15, 2);
            $t->string('currency', 3)->default('COP');
            $t->string('frequency', 20)->default('monthly');
            $t->decimal('variable_target', 15, 2)->default(0);
            $t->string('pay_grade', 40)->nullable();
            $t->date('effective_from')->index();
            $t->date('effective_to')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $t->timestamps();
            $t->index(['user_id', 'effective_from']);
        });
        Schema::create('integration_catalog', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('category', 40);
            $t->text('description');
            $t->string('auth_type', 30);
            $t->boolean('is_active')->default(true);
            $t->json('capabilities')->nullable();
            $t->timestamps();
        });
        Schema::create('organization_integrations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('integration_catalog_id')->constrained()->cascadeOnDelete();
            $t->string('status', 20)->default('configured')->index();
            $t->text('encrypted_config')->nullable();
            $t->timestamp('last_synced_at')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamps();
            $t->unique(['organization_id', 'integration_catalog_id']);
        });
        Schema::create('api_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $t->string('name');
            $t->string('token_prefix', 16)->index();
            $t->char('token_hash', 64)->unique();
            $t->json('abilities');
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamp('revoked_at')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('webhook_endpoints', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('url', 500);
            $t->text('secret');
            $t->json('events');
            $t->boolean('is_active')->default(true)->index();
            $t->unsignedSmallInteger('failure_count')->default(0);
            $t->timestamp('last_delivered_at')->nullable();
            $t->timestamps();
        });
        Schema::create('sso_connections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('protocol', 20)->default('oidc');
            $t->string('issuer_url', 500);
            $t->string('client_id');
            $t->text('client_secret');
            $t->string('authorization_endpoint', 500);
            $t->string('token_endpoint', 500);
            $t->string('jwks_uri', 500);
            $t->json('allowed_domains')->nullable();
            $t->boolean('is_enabled')->default(false)->index();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });
        Schema::create('compliance_controls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('framework', 30)->index();
            $t->string('control_code', 40);
            $t->string('title');
            $t->text('evidence')->nullable();
            $t->string('status', 20)->default('planned')->index();
            $t->foreignId('owner_id')->nullable()->constrained('admins')->nullOnDelete();
            $t->date('next_review_at')->nullable();
            $t->timestamps();
            $t->unique(['organization_id', 'framework', 'control_code']);
        });
        foreach ([['microsoft-365', 'Microsoft 365', 'identity', 'Sincroniza identidad, calendario y colaboración.', 'oauth2', ['identity', 'calendar']], ['google-workspace', 'Google Workspace', 'identity', 'Directorio, calendario y aprovisionamiento.', 'oauth2', ['identity', 'calendar']], ['slack', 'Slack', 'collaboration', 'Notificaciones y flujos de personas.', 'oauth2', ['notifications']], ['microsoft-teams', 'Microsoft Teams', 'collaboration', 'Aprobaciones y alertas dentro de Teams.', 'oauth2', ['notifications']], ['generic-webhook', 'Webhook empresarial', 'developer', 'Eventos firmados para cualquier plataforma.', 'secret', ['events']], ['scim', 'SCIM 2.0', 'identity', 'Aprovisionamiento estándar de usuarios.', 'bearer', ['provisioning']]] as [$slug,$name,$category,$description,$auth,$capabilities]) {
            DB::table('integration_catalog')->insert(['slug' => $slug, 'name' => $name, 'category' => $category, 'description' => $description, 'auth_type' => $auth, 'capabilities' => json_encode($capabilities), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['compliance_controls', 'sso_connections', 'webhook_endpoints', 'api_tokens', 'organization_integrations', 'integration_catalog', 'compensation_records', 'course_enrollments', 'courses', 'attendance_entries', 'candidates', 'job_postings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
