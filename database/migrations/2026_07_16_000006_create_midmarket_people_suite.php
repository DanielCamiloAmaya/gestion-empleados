<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 30)->index();
            $table->decimal('annual_allowance', 6, 2)->default(0);
            $table->decimal('carryover_max', 6, 2)->default(0);
            $table->unsignedSmallInteger('minimum_notice_days')->default(0);
            $table->unsignedSmallInteger('maximum_consecutive_days')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['organization_id', 'type']);
        });
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_policy_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('allocated', 7, 2)->default(0);
            $table->decimal('carried_over', 7, 2)->default(0);
            $table->decimal('adjustment', 7, 2)->default(0);
            $table->decimal('used', 7, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'leave_policy_id', 'year']);
        });
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('name');
            $table->string('country_code', 2)->default('CO');
            $table->timestamps();
            $table->unique(['organization_id', 'date']);
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('leave_policy_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('title');
            $table->string('category', 40)->index();
            $table->string('original_name');
            $table->string('storage_path', 500)->unique();
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->boolean('requires_signature')->default(false)->index();
            $table->date('expires_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'category']);
        });
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('signer_name');
            $table->char('document_sha256', 64);
            $table->char('signature_hash', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();
            $table->unique(['employee_document_id', 'user_id']);
        });

        Schema::create('review_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('name');
            $table->string('type', 30)->default('manager');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
        });
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('performance_score')->nullable();
            $table->unsignedTinyInteger('potential_score')->nullable();
            $table->text('summary')->nullable();
            $table->text('strengths')->nullable();
            $table->text('development_areas')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->unique(['review_cycle_id', 'user_id']);
        });

        Schema::create('offboarding_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->date('last_day')->index();
            $table->string('reason', 40);
            $table->string('risk_level', 20)->default('standard');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('offboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offboarding_case_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 40);
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        foreach (DB::table('organizations')->pluck('id') as $organizationId) {
            foreach ([
                ['Vacaciones', 'vacation', 15, 5, 5, 15],
                ['Licencia medica', 'medical', 0, 0, 0, null],
                ['Asuntos personales', 'personal', 3, 0, 2, 3],
                ['Licencia parental', 'parental', 0, 0, 30, null],
                ['Otra ausencia', 'other', 0, 0, 1, null],
            ] as [$name, $type, $allowance, $carry, $notice, $maximum]) {
                DB::table('leave_policies')->insert([
                    'organization_id' => $organizationId, 'name' => $name, 'type' => $type,
                    'annual_allowance' => $allowance, 'carryover_max' => $carry,
                    'minimum_notice_days' => $notice, 'maximum_consecutive_days' => $maximum,
                    'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_tasks');
        Schema::dropIfExists('offboarding_cases');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('review_cycles');
        Schema::dropIfExists('document_signatures');
        Schema::dropIfExists('employee_documents');
        Schema::table('leave_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('leave_policy_id'));
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_policies');
    }
};
