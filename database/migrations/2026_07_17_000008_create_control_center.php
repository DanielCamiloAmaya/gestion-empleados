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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('lifecycle_status', 30)->default('active')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->unsignedInteger('seat_limit')->default(100);
        });

        Schema::create('platform_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role', 30)->index();
            $table->string('status', 30)->default('invited')->index();
            $table->boolean('mfa_enabled')->default(false);
            $table->text('mfa_secret')->nullable();
            $table->json('mfa_recovery_codes')->nullable();
            $table->timestamp('mfa_confirmed_at')->nullable();
            $table->char('invitation_token_hash', 64)->nullable()->unique();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('monthly_price_cents')->default(0);
            $table->unsignedBigInteger('annual_price_cents')->default(0);
            $table->unsignedInteger('included_seats')->default(50);
            $table->json('limits')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('trialing')->index();
            $table->unsignedInteger('licensed_seats');
            $table->string('billing_cycle', 20)->default('annual');
            $table->string('external_customer_id')->nullable()->index();
            $table->string('external_subscription_id')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('legal_entities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('country_code', 2);
            $table->string('tax_id_type', 30)->default('NIT');
            $table->string('tax_identifier', 80);
            $table->string('registration_number', 100)->nullable();
            $table->text('registered_address')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->string('verification_status', 30)->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['country_code', 'tax_id_type', 'tax_identifier']);
        });

        Schema::create('organization_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->text('verification_token');
            $table->string('verification_status', 30)->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('organization_owner_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->char('token_hash', 64)->unique();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'email']);
        });

        Schema::create('support_access_grants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_user_id')->constrained()->restrictOnDelete();
            $table->string('ticket_reference', 100);
            $table->text('reason');
            $table->json('scopes');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedSmallInteger('duration_minutes');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->foreignId('revoked_by_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('platform_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name');
            $table->string('event', 100)->index();
            $table->string('target_type', 120);
            $table->string('target_id', 80)->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->string('request_id', 100)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->char('previous_hash', 64)->nullable();
            $table->char('entry_hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        $plans = [
            ['code' => 'growth', 'name' => 'Growth', 'description' => 'Operación de personas para compañías en expansión.', 'monthly_price_cents' => 120000, 'annual_price_cents' => 1200000, 'included_seats' => 100, 'limits' => ['employees' => 100, 'legal_entities' => 1, 'domains' => 2], 'features' => ['core_hr', 'leave', 'onboarding', 'documents']],
            ['code' => 'business', 'name' => 'Business', 'description' => 'Gobierno, automatización y analítica para mercado medio.', 'monthly_price_cents' => 290000, 'annual_price_cents' => 2900000, 'included_seats' => 500, 'limits' => ['employees' => 500, 'legal_entities' => 5, 'domains' => 10], 'features' => ['core_hr', 'leave', 'onboarding', 'documents', 'reviews', 'offboarding', 'api']],
            ['code' => 'enterprise', 'name' => 'Enterprise', 'description' => 'Identidad federada, cumplimiento y operación multinacional.', 'monthly_price_cents' => 0, 'annual_price_cents' => 0, 'included_seats' => 1000, 'limits' => ['employees' => 10000, 'legal_entities' => 100, 'domains' => 250], 'features' => ['all', 'sso', 'scim', 'advanced_audit', 'sla']],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->insert(array_merge($plan, [
                'currency' => 'USD',
                'is_active' => true,
                'limits' => json_encode($plan['limits']),
                'features' => json_encode($plan['features']),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $enterprisePlanId = DB::table('plans')->where('code', 'enterprise')->value('id');
        foreach (DB::table('organizations')->get() as $organization) {
            DB::table('organizations')->where('id', $organization->id)->update([
                'lifecycle_status' => $organization->is_active ? 'active' : 'suspended',
                'activated_at' => $organization->is_active ? now() : null,
                'seat_limit' => 1000,
            ]);
            DB::table('subscriptions')->insert([
                'organization_id' => $organization->id,
                'plan_id' => $enterprisePlanId,
                'status' => $organization->is_active ? 'active' : 'paused',
                'licensed_seats' => 1000,
                'billing_cycle' => 'annual',
                'current_period_starts_at' => now(),
                'current_period_ends_at' => now()->addYear(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (filled($organization->tax_identifier)) {
                DB::table('legal_entities')->insert([
                    'uuid' => (string) Str::uuid(),
                    'organization_id' => $organization->id,
                    'legal_name' => $organization->legal_name ?: $organization->name,
                    'trade_name' => $organization->name,
                    'country_code' => $organization->country_code,
                    'tax_id_type' => 'NIT',
                    'tax_identifier' => $organization->tax_identifier,
                    'is_primary' => true,
                    'verification_status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_audit_logs');
        Schema::dropIfExists('support_access_grants');
        Schema::dropIfExists('organization_owner_invitations');
        Schema::dropIfExists('organization_domains');
        Schema::dropIfExists('legal_entities');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('platform_users');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'lifecycle_status', 'activated_at', 'suspended_at', 'suspension_reason',
                'onboarding_completed_at', 'seat_limit',
            ]);
        });
    }
};
