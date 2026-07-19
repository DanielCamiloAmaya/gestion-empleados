<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('auth_version')->default(1);
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->string('status', 30)->default('active')->index();
            $table->unsignedInteger('auth_version')->default(1);
            $table->timestamp('disabled_at')->nullable();
        });

        Schema::table('platform_users', function (Blueprint $table) {
            $table->unsignedInteger('auth_version')->default(1);
        });

        Schema::create('admin_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invited_by')->constrained('admins')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('email');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'email']);
        });

        Schema::create('account_recovery_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id');
            $table->string('email');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['actor_type', 'actor_id']);
        });

        Schema::table('compliance_controls', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('review_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('compliance_controls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['verified_at', 'review_note']);
        });
        Schema::dropIfExists('account_recovery_tokens');
        Schema::dropIfExists('admin_invitations');

        Schema::table('platform_users', fn (Blueprint $table) => $table->dropColumn('auth_version'));
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['status', 'auth_version', 'disabled_at']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('auth_version'));
    }
};
