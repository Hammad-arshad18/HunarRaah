<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('public_reference')->unique();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('course_id')->constrained()->restrictOnDelete();
            $t->string('title_snapshot');
            $t->string('learner_name');
            $t->string('terms_version');
            $t->char('currency', 3);
            $t->unsignedBigInteger('subtotal_minor');
            $t->unsignedBigInteger('tax_minor')->default(0);
            $t->unsignedBigInteger('total_minor');
            $t->enum('payment_status', ['pending', 'paid', 'expired', 'failed', 'refunded'])->default('pending');
            $t->unsignedBigInteger('refunded_minor')->default(0);
            $t->enum('dispute_status', ['none', 'open', 'won', 'lost'])->default('none');
            $t->boolean('financial_exception')->default(false);
            $t->timestamps();
            $t->index(['payment_status', 'updated_at']);
        });
        Schema::table('enrollments', fn (Blueprint $t) => $t->foreign('current_order_id')->references('id')->on('orders')->restrictOnDelete());
        Schema::create('payment_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $t->string('session_id')->nullable()->unique();
            $t->string('payment_intent_id')->nullable()->unique();
            $t->string('idempotency_key')->unique();
            $t->string('status')->default('created');
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('last_reconciled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('refunds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->restrictOnDelete();
            $t->string('provider_refund_id')->unique();
            $t->unsignedBigInteger('amount_minor');
            $t->char('currency', 3);
            $t->string('status');
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('webhook_events', function (Blueprint $t) {
            $t->id();
            $t->string('provider');
            $t->string('event_id');
            $t->string('event_type');
            $t->text('payload');
            $t->string('processing_status')->default('pending');
            $t->unsignedInteger('attempts')->default(0);
            $t->timestamp('received_at');
            $t->timestamp('processed_at')->nullable();
            $t->string('error_code')->nullable();
            $t->timestamps();
            $t->unique(['provider', 'event_id']);
            $t->index(['processing_status', 'received_at']);
        });
        Schema::create('certificates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('current_enrollment_id')->nullable()->unique();
            $t->string('credential_id')->unique();
            $t->string('verification_token', 64)->unique();
            $t->string('learner_name');
            $t->string('course_title');
            $t->string('issuer_name');
            $t->string('signatory')->nullable();
            $t->enum('status', ['valid', 'suspended', 'revoked', 'superseded'])->default('valid');
            $t->timestamp('public_enabled_at')->nullable();
            $t->string('consent_version')->nullable();
            $t->timestamp('issued_at');
            $t->unsignedBigInteger('supersedes_id')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->string('private_pdf_path')->nullable();
            $t->enum('generation_status', ['pending', 'ready', 'failed'])->default('pending');
            $t->timestamps();
            $t->foreign('current_enrollment_id')->references('id')->on('enrollments')->restrictOnDelete();
            $t->foreign('supersedes_id')->references('id')->on('certificates')->restrictOnDelete();
        });
        Schema::create('notification_deliveries', function (Blueprint $t) {
            $t->id();
            $t->string('dedupe_key')->unique();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('type');
            $t->string('subject_reference');
            $t->string('status')->default('pending');
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notification_deliveries', 'certificates', 'webhook_events', 'refunds', 'payment_attempts'] as $name) {
            Schema::dropIfExists($name);
        } Schema::table('enrollments', fn (Blueprint $t) => $t->dropForeign(['current_order_id']));
        Schema::dropIfExists('orders');
    }
};
