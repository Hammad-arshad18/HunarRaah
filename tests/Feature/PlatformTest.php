<?php

namespace Tests\Feature;

use App\Actions\CompleteLesson;
use App\Actions\GrantEnrollment;
use App\Actions\IssueCertificate;
use App\Actions\ReconcileOrderPayment;
use App\Jobs\GenerateCertificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private function course(array $attributes = []): Course
    {
        $c = Course::create(array_merge(['title' => 'Sample course', 'slug' => fake()->unique()->slug(), 'summary' => 'Learn a skill', 'description' => 'Safe description', 'outcomes' => ['Understand the material'], 'format' => 'recorded', 'instructor_name' => 'Sample instructor', 'instructor_bio' => 'Development fixture', 'price_minor' => 0, 'currency' => 'AED', 'status' => 'published', 'sales_visible' => true, 'accessible_content_confirmed' => true, 'access_policy' => 'Ongoing access', 'completion_policy' => 'All required lessons'], $attributes));
        $m = $c->modules()->create(['title' => 'First chapter', 'position' => 1]);
        $m->lessons()->create(['title' => 'A lesson', 'type' => 'text', 'position' => 1, 'required' => true, 'published' => true, 'body' => 'PROTECTED-LESSON-BODY']);

        return $c;
    }

    public function test_registration_rejects_privilege_injection_and_requires_terms(): void
    {
        $this->post('/register', ['name' => 'Student', 'email' => 'new@example.com', 'password' => 'a-long-test-password', 'password_confirmation' => 'a-long-test-password', 'role' => 'admin', 'terms' => true])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
        $this->post('/register', ['name' => 'Student', 'email' => 'new@example.com', 'password' => 'a-long-test-password', 'password_confirmation' => 'a-long-test-password'])->assertSessionHasErrors('terms');
    }

    public function test_signed_webhook_delivery_is_durable_and_deduplicated(): void
    {
        Queue::fake();
        config(['platform.stripe.webhook_secret' => 'whsec_fixture']);
        $body = json_encode(['id' => 'evt_durable', 'object' => 'event', 'type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_test_fixture', 'object' => 'checkout.session']]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_fixture');
        for ($i = 0; $i < 2; $i++) {
            $this->call('POST', '/webhooks/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature], $body)->assertNoContent();
        }
        $this->assertDatabaseCount('webhook_events', 1);
    }

    public function test_curriculum_is_locked_and_images_reject_active_content(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'two_factor_secret' => encrypt('fixture'), 'two_factor_confirmed_at' => now()]);
        $course = $this->course();
        app(GrantEnrollment::class)->free(User::factory()->create(), $course);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $this->post('/admin/courses/'.$course->id.'/modules', ['title' => 'Changed structure', 'position' => 2])->assertStatus(409);
        $this->post('/admin/courses/'.$course->id.'/image', ['slot' => 'cover', 'image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');
        Storage::fake('public');
        $this->post('/admin/courses/'.$course->id.'/image', ['slot' => 'cover', 'image' => UploadedFile::fake()->image('safe.jpg')])->assertRedirect();
        $this->assertStringEndsWith('.png', $course->fresh()->cover_path);
        Storage::disk('public')->assertExists($course->fresh()->cover_path);
    }

    public function test_certificate_requires_completed_required_curriculum(): void
    {
        $user = User::factory()->create();
        $enrollment = app(GrantEnrollment::class)->free($user, $this->course());
        $this->expectException(HttpException::class);
        app(IssueCertificate::class)->execute($user, $enrollment);
    }

    public function test_free_enrollment_is_unique_and_paid_endpoint_fails_closed(): void
    {
        $u = User::factory()->create();
        $c = $this->course();
        $this->actingAs($u)->post('/courses/'.$c->id.'/enroll-free')->assertRedirect();
        $this->post('/courses/'.$c->id.'/enroll-free')->assertRedirect();
        $this->assertDatabaseCount('enrollments', 1);
        $paid = $this->course(['price_minor' => 49900]);
        $this->post('/courses/'.$paid->id.'/enroll-free')->assertStatus(409);
    }

    public function test_unverified_suspended_and_cross_course_users_cannot_study(): void
    {
        $a = $this->course();
        $b = $this->course();
        $u = User::factory()->create();
        app(GrantEnrollment::class)->free($u, $a);
        $this->actingAs($u)->get('/learn/'.$a->id.'/'.$b->lessons()->first()->id)->assertNotFound();
        $this->get('/learn/'.$b->id)->assertForbidden();
        $this->post('/lessons/'.$b->lessons()->first()->id.'/playback-token')->assertForbidden();
        $u->forceFill(['suspended_at' => now()])->save();
        $this->get('/learn/'.$a->id)->assertForbidden();
        $this->assertGuest();
        $this->actingAs(User::factory()->unverified()->create())->post('/courses/'.$a->id.'/enroll-free')->assertRedirect('/email/verify');
    }

    public function test_public_pages_do_not_leak_private_content(): void
    {
        $c = $this->course();
        $l = $c->lessons()->first();
        $l->update(['video_uid' => 'private-video-identifier']);
        $this->get('/courses/'.$c->slug)->assertOk()->assertDontSee('PROTECTED-LESSON-BODY')->assertDontSee('private-video-identifier');
        $this->course(['status' => 'draft', 'title' => 'SECRET DRAFT']);
        $this->get('/courses')->assertDontSee('SECRET DRAFT');
    }

    public function test_completion_is_idempotent_and_optional_lessons_do_not_block(): void
    {
        $u = User::factory()->create();
        $c = $this->course();
        $e = app(GrantEnrollment::class)->free($u, $c);
        $l = $c->lessons()->first();
        $l->module->lessons()->create(['title' => 'Optional', 'type' => 'text', 'position' => 2, 'required' => false, 'published' => true, 'body' => 'Optional']);
        app(CompleteLesson::class)->execute($u, $l);
        $time = $e->fresh()->completed_at;
        app(CompleteLesson::class)->execute($u, $l);
        $this->assertDatabaseCount('lesson_progress', 1);
        $this->assertEquals($time, $e->fresh()->completed_at);
        $c->update(['status' => 'archived', 'sales_visible' => false]);
        $this->actingAs($u)->get('/learn/'.$c->id.'/'.$l->id)->assertOk();
    }

    public function test_admin_role_and_mfa_are_enforced(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get('/admin/courses')->assertForbidden();
        $u->forceFill(['role' => 'admin'])->save();
        $this->get('/admin/courses')->assertRedirect('/settings/security');
    }

    public function test_unowned_orders_and_forged_return_do_not_grant_access(): void
    {
        $c = $this->course(['price_minor' => 49900]);
        $u = User::factory()->create();
        $order = $this->order($u, $c);
        $this->actingAs(User::factory()->create())->get('/orders/'.$order->public_reference)->assertNotFound();
        $this->actingAs($u)->get('/orders/'.$order->public_reference.'?success=true')->assertOk();
        $this->assertDatabaseCount('enrollments', 0);
    }

    private function order(User $u, Course $c): Order
    {
        $o = Order::create(['public_reference' => fake()->uuid(), 'user_id' => $u->id, 'course_id' => $c->id, 'title_snapshot' => $c->title, 'learner_name' => $u->name, 'terms_version' => 'test', 'currency' => 'AED', 'subtotal_minor' => 49900, 'tax_minor' => 0, 'total_minor' => 49900]);
        $o->attempt()->create(['session_id' => 'cs_'.fake()->uuid(), 'idempotency_key' => fake()->uuid()]);

        return $o;
    }

    private function state(Order $o, array $extra = []): array
    {
        return array_merge(['id' => $o->attempt->session_id, 'metadata' => ['order_reference' => $o->public_reference], 'client_reference_id' => $o->public_reference, 'amount_total' => 49900, 'currency' => 'aed', 'livemode' => false, 'payment_status' => 'paid', 'status' => 'complete', 'refunded_minor' => 0, 'dispute_status' => 'none'], $extra);
    }

    public function test_payment_requires_paid_state_and_duplicate_fulfillment_is_safe(): void
    {
        $u = User::factory()->create();
        $o = $this->order($u, $this->course(['price_minor' => 49900]));
        $a = app(ReconcileOrderPayment::class);
        $a->execute($o->attempt, $this->state($o, ['payment_status' => 'unpaid']));
        $this->assertDatabaseCount('enrollments', 0);
        $a->execute($o->attempt, $this->state($o));
        $a->execute($o->attempt, $this->state($o));
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_refund_and_dispute_states_do_not_undo_admin_restrictions(): void
    {
        $u = User::factory()->create();
        $o = $this->order($u, $this->course(['price_minor' => 49900]));
        $a = app(ReconcileOrderPayment::class);
        $a->execute($o->attempt, $this->state($o));
        $e = Enrollment::first();
        $a->execute($o->attempt, $this->state($o, ['refunded_minor' => 100]));
        $this->assertSame('active', $e->fresh()->status);
        $a->execute($o->attempt, $this->state($o, ['dispute_status' => 'open']));
        $this->assertSame('suspended', $e->fresh()->status);
        $e->update(['admin_restriction' => 'Owner hold']);
        $a->execute($o->attempt, $this->state($o, ['dispute_status' => 'won']));
        $this->assertSame('suspended', $e->fresh()->status);
        $a->execute($o->attempt, $this->state($o, ['refunded_minor' => 49900]));
        $a->execute($o->attempt, $this->state($o));
        $this->assertSame('revoked', $e->fresh()->status);
    }

    public function test_payment_mismatch_fails_closed(): void
    {
        $o = $this->order(User::factory()->create(), $this->course(['price_minor' => 49900]));
        $this->expectException(\RuntimeException::class);
        app(ReconcileOrderPayment::class)->execute($o->attempt, $this->state($o, ['amount_total' => 1]));
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        config(['platform.stripe.webhook_secret' => 'whsec_fixture']);
        $this->postJson('/webhooks/stripe', ['id' => 'evt_fake'], ['Stripe-Signature' => 'forged'])->assertStatus(400);
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_certificate_is_private_idempotent_and_owner_only(): void
    {
        Queue::fake();
        $u = User::factory()->create(['name' => 'مريم أحمد']);
        $c = $this->course();
        $e = app(GrantEnrollment::class)->free($u, $c);
        app(CompleteLesson::class)->execute($u, $c->lessons()->first());
        $certificate = app(IssueCertificate::class)->execute($u, $e);
        app(IssueCertificate::class)->execute($u, $e);
        $this->assertDatabaseCount('certificates', 1);
        $this->get('/certificates/verify/'.$certificate->verification_token)->assertNotFound()->assertDontSee('مريم أحمد');
        $this->actingAs(User::factory()->create())->get('/certificates/'.$certificate->credential_id.'/download')->assertNotFound();
        $this->actingAs($u)->patch('/certificates/'.$certificate->credential_id.'/sharing', ['enabled' => true, 'consent' => true])->assertRedirect();
        $this->get('/certificates/verify/'.$certificate->verification_token)->assertOk()->assertSee('مريم أحمد');
        $certificate->update(['status' => 'revoked']);
        $this->get('/certificates/verify/'.$certificate->verification_token)->assertSee('revoked')->assertDontSee('مريم أحمد');
    }

    public function test_certificate_pdf_job_retries_do_not_create_another_credential(): void
    {
        Queue::fake();
        Storage::fake('local');
        $u = User::factory()->create();
        $c = $this->course();
        $e = app(GrantEnrollment::class)->free($u, $c);
        app(CompleteLesson::class)->execute($u, $c->lessons()->first());
        $cert = app(IssueCertificate::class)->execute($u, $e);
        $job = new GenerateCertificate($cert->id);
        $job->handle();
        $job->handle();
        $this->assertDatabaseCount('certificates', 1);
        $this->assertSame('ready', $cert->fresh()->generation_status);
        Storage::disk('local')->assertExists($cert->fresh()->private_pdf_path);
    }

    public function test_join_window_and_cancellation_do_not_mark_attendance(): void
    {
        $u = User::factory()->create();
        $c = $this->course(['format' => 'live']);
        $l = $c->lessons()->first();
        $l->update(['type' => 'live']);
        app(GrantEnrollment::class)->free($u, $c);
        $s = LiveSession::create(['lesson_id' => $l->id, 'provider' => 'Google Meet', 'join_url' => 'https://meet.google.com/abc-defg-hij', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'timezone' => 'Asia/Dubai']);
        $this->actingAs($u)->post('/live-sessions/'.$s->id.'/join')->assertStatus(409);
        $s->update(['starts_at' => now()->subMinute()]);
        $this->post('/live-sessions/'.$s->id.'/join')->assertRedirect();
        $this->assertDatabaseCount('lesson_progress', 0);
        $s->update(['status' => 'cancelled']);
        $this->post('/live-sessions/'.$s->id.'/join')->assertStatus(409);
    }
}
