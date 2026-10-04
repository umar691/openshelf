<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('slug', 220)->unique();
            $table->text('description');
            $table->string('category', 100)->index();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamps();
            $table->index(['freelancer_id', 'status']);
            $table->index('title');
        });

        Schema::create('gig_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained()->cascadeOnDelete();
            $table->string('tier', 16);
            $table->string('title', 100);
            $table->text('description');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3)->default('PKR');
            $table->unsignedSmallInteger('delivery_days');
            $table->unsignedSmallInteger('revisions')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
            $table->unique(['gig_id', 'tier']);
        });

        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('slug', 220)->unique();
            $table->longText('description');
            $table->json('requirements');
            $table->string('employment_type', 24)->index();
            $table->string('location_type', 24)->index();
            $table->string('location', 180)->nullable();
            $table->unsignedBigInteger('salary_min_minor')->nullable();
            $table->unsignedBigInteger('salary_max_minor')->nullable();
            $table->char('salary_currency', 3)->default('PKR');
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'location_type', 'created_at']);
            $table->index(['employer_id', 'status']);
            $table->index('title');
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('users')->cascadeOnDelete();
            $table->text('cover_letter');
            $table->string('status', 24)->default('submitted')->index();
            $table->string('resume_path', 1024)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['job_post_id', 'applicant_id']);
            $table->index(['applicant_id', 'status', 'created_at']);
        });

        Schema::create('escrow_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('gig_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->string('status', 24)->default('draft')->index();
            $table->char('currency', 3);
            $table->unsignedBigInteger('total_minor');
            $table->unsignedBigInteger('funded_minor')->default(0);
            $table->unsignedBigInteger('released_minor')->default(0);
            $table->unsignedBigInteger('refunded_minor')->default(0);
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'status']);
            $table->index(['freelancer_id', 'status']);
        });

        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['escrow_contract_id', 'position']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->morphs('payable');
            $table->string('gateway', 32)->index();
            $table->string('gateway_reference', 180)->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 24)->default('pending')->index();
            $table->json('gateway_metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'gateway_reference']);
            $table->index(['payable_type', 'payable_id', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 32);
            $table->string('event_id', 180);
            $table->string('event_type', 100);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'event_id']);
        });

        Schema::create('contract_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->string('status', 24)->default('open')->index();
            $table->json('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['escrow_contract_id', 'status']);
        });

        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->restrictOnDelete();
            $table->string('gateway', 32);
            $table->string('provider_reference', 180)->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 24)->default('pending')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'status', 'created_at']);
        });

        Schema::create('platform_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('subject');
            $table->string('entry_type', 32);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('idempotency_key', 100)->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id', 'created_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('job_posts', fn (Blueprint $table) => $table->fullText(['title', 'description'], 'job_posts_search_fulltext'));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('job_posts')) {
            Schema::table('job_posts', fn (Blueprint $table) => $table->dropFullText('job_posts_search_fulltext'));
        }

        Schema::dropIfExists('platform_ledger_entries');
        Schema::dropIfExists('vendor_payouts');
        Schema::dropIfExists('contract_disputes');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('contract_milestones');
        Schema::dropIfExists('escrow_contracts');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_posts');
        Schema::dropIfExists('gig_packages');
        Schema::dropIfExists('gigs');
    }
};
