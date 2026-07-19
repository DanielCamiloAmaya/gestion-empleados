<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('cost_center', 50)->nullable()->unique();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 30)->nullable()->unique()->after('id');
            $table->string('job_title')->nullable()->after('last_name');
            $table->string('employment_status', 30)->default('active')->index();
            $table->string('employment_type', 30)->default('full_time');
            $table->date('hire_date')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('location')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->string('role', 30)->default('hr_admin')->index();
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type', 30);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('event', 60)->index();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['actor_type', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['role', 'last_login_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn([
                'employee_code', 'job_title', 'employment_status', 'employment_type',
                'hire_date', 'phone', 'location', 'manager_id', 'deleted_at',
            ]);
        });

        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropUnique(['cost_center']);
            $table->dropColumn(['description', 'cost_center', 'is_active']);
        });
    }
};
