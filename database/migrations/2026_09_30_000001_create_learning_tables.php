<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['student', 'admin'])->default('student');
            $table->timestamp('suspended_at')->nullable();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->text('description');
            $table->json('outcomes');
            $table->text('prerequisites')->nullable();
            $table->text('target_audience')->nullable();
            $table->string('level')->default('Beginner');
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->enum('format', ['recorded', 'live', 'hybrid']);
            $table->string('instructor_name');
            $table->text('instructor_bio');
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->char('currency', 3)->default('AED');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('sales_visible')->default(false);
            $table->timestamp('enrollment_deadline')->nullable();
            $table->boolean('certificate_enabled')->default(true);
            $table->boolean('recording_alternative')->default(false);
            $table->boolean('accessible_content_confirmed')->default(false);
            $table->text('access_policy');
            $table->text('completion_policy');
            $table->text('takedown_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'sales_visible']);
            $table->index('title');
        });
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->unsignedInteger('position');
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->enum('type', ['text', 'video', 'live']);
            $table->boolean('required')->default(true);
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position');
            $table->longText('body')->nullable();
            $table->string('video_uid')->nullable();
            $table->enum('video_status', ['pending', 'processing', 'ready', 'failed', 'removed'])->default('pending');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
        });
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('current_order_id')->nullable();
            $table->enum('source', ['free', 'paid', 'complimentary']);
            $table->enum('status', ['active', 'suspended', 'revoked'])->default('active');
            $table->timestamp('access_ends_at')->nullable();
            $table->timestamp('granted_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('restriction_reason')->nullable();
            $table->text('admin_restriction')->nullable();
            $table->string('terms_version');
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('lesson_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position_seconds')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->enum('completion_source', ['self_attested', 'attendance', 'admin_correction'])->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['enrollment_id', 'lesson_id']);
        });
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique()->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->text('join_url');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone');
            $table->enum('status', ['scheduled', 'cancelled', 'completed'])->default('scheduled');
            $table->text('message')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['status', 'starts_at']);
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'live_sessions', 'lesson_progress', 'enrollments', 'lessons', 'modules', 'courses'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'suspended_at']));
    }
};
