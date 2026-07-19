<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['onboarding_task_id', 'version']);
            $table->index(['onboarding_task_id', 'status']);
        });

        Schema::create('onboarding_deliverable_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_submission_id')->constrained()->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('storage_path', 500)->unique();
            $table->string('mime_type', 150);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_deliverable_files');
        Schema::dropIfExists('onboarding_submissions');
    }
};
