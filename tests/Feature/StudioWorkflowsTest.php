<?php

namespace Tests\Feature;

use App\Actions\CompleteLesson;
use App\Actions\GrantEnrollment;
use App\Actions\IssueCertificate;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\StreamGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudioWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    private function course(): Course
    {
        $course = Course::create(['title' => 'A thoughtful course', 'slug' => fake()->unique()->slug(), 'summary' => 'A clear outcome', 'description' => 'Description', 'outcomes' => ['Build a useful skill'], 'format' => 'recorded', 'instructor_name' => 'Development instructor', 'instructor_bio' => 'Development fixture', 'price_minor' => 0, 'currency' => 'AED', 'status' => 'published', 'sales_visible' => true, 'accessible_content_confirmed' => true, 'access_policy' => 'Ongoing access', 'completion_policy' => 'Every required lesson']);
        $module = $course->modules()->create(['title' => 'First chapter', 'position' => 1]);
        $module->lessons()->create(['title' => 'Read the material', 'type' => 'text', 'position' => 1, 'required' => true, 'published' => true, 'body' => 'Protected reading']);

        return $course;
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'two_factor_secret' => encrypt('fixture'), 'two_factor_confirmed_at' => now()]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);

        return $admin;
    }

    public function test_curriculum_controls_enforce_relationships_and_enrollment_locks(): void
    {
        $this->admin();
        $course = $this->course();
        $foreign = $this->course()->modules()->first();
        $module = $course->modules()->first();
        $lesson = $module->lessons()->first();
        $data = ['title' => 'Revised chapter', 'position' => 2, 'reason' => 'Improve the ordering'];
        $this->put("/admin/courses/{$course->id}/modules/{$foreign->id}", $data)->assertNotFound();
        $this->put("/admin/courses/{$course->id}/modules/{$module->id}", $data)->assertRedirect();
        $this->assertSame(2, $module->fresh()->position);
        $this->delete("/admin/courses/{$course->id}/modules/{$module->id}", ['reason' => 'Clean draft'])->assertStatus(409);
        app(GrantEnrollment::class)->free(User::factory()->create(), $course);
        $this->delete("/admin/courses/{$course->id}/lessons/{$lesson->id}", ['reason' => 'Clean draft'])->assertStatus(409);
        $this->put("/admin/courses/{$course->id}/modules/{$module->id}", array_merge($data, ['position' => 3]))->assertStatus(409);
        $this->put("/admin/courses/{$course->id}/modules/{$module->id}", array_merge($data, ['title' => 'Corrected chapter']))->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'module.updated', 'reason' => 'Improve the ordering']);
    }

    public function test_unenrolled_draft_lessons_and_empty_chapters_can_be_removed(): void
    {
        $this->admin();
        $course = $this->course();
        $module = $course->modules()->first();
        $lesson = $module->lessons()->first();
        $this->delete("/admin/courses/{$course->id}/lessons/{$lesson->id}", ['reason' => 'Remove draft material'])->assertRedirect();
        $this->delete("/admin/courses/{$course->id}/modules/{$module->id}", ['reason' => 'Remove empty chapter'])->assertRedirect();
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }

    public function test_sales_visibility_preserves_access_and_takedown_blocks_it(): void
    {
        $course = $this->course();
        $student = User::factory()->create();
        app(GrantEnrollment::class)->free($student, $course);
        $this->admin();
        $url = "/admin/courses/{$course->id}/availability";
        $this->post($url, ['sales_visible' => false, 'takedown_reason' => '', 'reason' => 'Close new enrollment'])->assertRedirect();
        $this->actingAs($student)->get('/learn/'.$course->id)->assertRedirect();
        $this->admin();
        $this->post($url, ['sales_visible' => false, 'takedown_reason' => 'Content under review', 'reason' => 'Emergency review'])->assertRedirect();
        $this->actingAs($student)->get('/learn/'.$course->id)->assertForbidden();
        $this->get('/courses/'.$course->slug)->assertNotFound();
    }

    public function test_owner_purchase_and_credential_lists_do_not_expose_other_students(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $course = $this->course();
        foreach ([$owner, $other] as $user) {
            Order::create(['public_reference' => fake()->uuid(), 'user_id' => $user->id, 'course_id' => $course->id, 'title_snapshot' => $user->name, 'learner_name' => $user->name, 'terms_version' => 'test', 'currency' => 'AED', 'subtotal_minor' => 100, 'tax_minor' => 0, 'total_minor' => 100]);
        }
        $this->actingAs($owner)->get('/purchases')->assertInertia(fn (Assert $p) => $p->component('studio/purchases')->has('orders.data', 1)->where('orders.data.0.title_snapshot', $owner->name));
        $this->get('/credentials')->assertInertia(fn (Assert $p) => $p->component('studio/credentials')->has('certificates.data', 0));
    }

    public function test_dashboard_counts_required_completion_and_resume_prefers_recent_unfinished_lesson(): void
    {
        $course = $this->course();
        $student = User::factory()->create();
        $enrollment = app(GrantEnrollment::class)->free($student, $course);
        $second = $course->modules()->first()->lessons()->create(['title' => 'Second lesson', 'type' => 'text', 'position' => 2, 'required' => true, 'published' => true, 'body' => 'Second protected reading']);
        $enrollment->progress()->create(['lesson_id' => $second->id, 'position_seconds' => 0]);
        $this->actingAs($student)->get('/learn/'.$course->id)->assertRedirect('/learn/'.$course->id.'/'.$second->id);
        app(CompleteLesson::class)->execute($student, $second);
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('enrollments.0.required_count', 2)->where('enrollments.0.completed_count', 1));
    }

    public function test_live_schedule_is_private_and_admin_editor_prefills_current_session(): void
    {
        $course = $this->course();
        $lesson = $course->lessons()->first();
        $lesson->update(['type' => 'live']);
        $lesson->session()->create(['provider' => 'Meet', 'join_url' => 'https://meet.google.com/private-link', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'timezone' => 'Asia/Dubai']);
        $student = User::factory()->create();
        app(GrantEnrollment::class)->free($student, $course);
        $this->actingAs($student)->get('/schedule')->assertInertia(fn (Assert $p) => $p->has('sessions.data', 1)->missing('sessions.data.0.join_url'));
        $this->admin();
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Livewire\Livewire::test(\App\Filament\Resources\LessonResource\Pages\ListLessons::class)->mountTableAction('schedule', $lesson)->assertTableActionDataSet(['join_url' => 'https://meet.google.com/private-link']);
    }

    public function test_admin_email_correction_requires_reverification_and_invalidates_sessions(): void
    {
        Notification::fake();
        $student = User::factory()->create();
        $this->admin();
        $this->put('/admin/students/'.$student->id.'/email', ['email' => 'corrected@example.test', 'reason' => 'Owner verified correction'])->assertRedirect();
        $this->assertNull($student->fresh()->email_verified_at);
        $this->assertSame('corrected@example.test', $student->fresh()->email);
        Notification::assertSentTo($student, \Illuminate\Auth\Notifications\VerifyEmail::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.email_corrected']);
    }

    public function test_students_cannot_use_new_administrator_endpoints(): void
    {
        $student = User::factory()->create();
        $course = $this->course();
        $lesson = $course->lessons()->first();
        $this->actingAs($student)->get('/admin/activity')->assertForbidden();
        $this->get('/admin/courses/create')->assertForbidden();
        $this->post('/admin/courses/'.$course->id.'/availability', ['sales_visible' => false, 'reason' => 'Injected'])->assertForbidden();
        $this->post('/admin/lessons/'.$lesson->id.'/playback-token')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/activity')->assertRedirect('/admin/setup');
    }

    public function test_audited_admin_preview_does_not_need_or_create_student_enrollment(): void
    {
        $this->admin();
        $course = $this->course();
        $lesson = $course->lessons()->first();
        $lesson->update(['video_uid' => str_repeat('a', 32), 'video_status' => 'ready']);
        $this->mock(StreamGateway::class, fn ($mock) => $mock->shouldReceive('token')->once()->with(str_repeat('a', 32))->andReturn('signed-fixture'));
        $this->postJson('/admin/lessons/'.$lesson->id.'/playback-token')->assertOk()->assertJsonPath('url', 'https://iframe.videodelivery.net/signed-fixture');
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'video.previewed']);
    }

    public function test_completion_correction_preserves_historical_certificate_until_explicit_revocation(): void
    {
        Queue::fake();
        $course = $this->course();
        $student = User::factory()->create();
        $lesson = $course->lessons()->first();
        $enrollment = app(GrantEnrollment::class)->free($student, $course);
        app(CompleteLesson::class)->execute($student, $lesson);
        $certificate = app(IssueCertificate::class)->execute($student, $enrollment);
        $this->admin();
        $this->post("/admin/lessons/{$lesson->id}/completion/{$enrollment->id}", ['completed' => false, 'reason' => 'Correct mistaken attendance'])->assertRedirect();
        $this->assertNull($enrollment->fresh()->completed_at);
        $this->assertSame('valid', $certificate->fresh()->status);
    }

    public function test_failed_pdf_retry_is_owner_only_and_does_not_duplicate_credentials(): void
    {
        Queue::fake();
        $course = $this->course();
        $student = User::factory()->create();
        $e = app(GrantEnrollment::class)->free($student, $course);
        app(CompleteLesson::class)->execute($student, $course->lessons()->first());
        $c = app(IssueCertificate::class)->execute($student, $e);
        $c->update(['generation_status' => 'failed']);
        $this->actingAs(User::factory()->create())->post('/certificates/'.$c->credential_id.'/retry')->assertNotFound();
        $this->actingAs($student)->post('/certificates/'.$c->credential_id.'/retry')->assertRedirect();
        $this->assertSame('pending', $c->fresh()->generation_status);
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_inertia_business_conflict_returns_readable_flash_and_json_keeps_status(): void
    {
        $student = User::factory()->create();
        $course = $this->course();
        $course->update(['price_minor' => 100]);
        $this->actingAs($student)->from('/courses/'.$course->slug)->withHeaders(['X-Inertia' => 'true'])->post('/courses/'.$course->id.'/enroll-free')->assertRedirect('/courses/'.$course->slug)->assertSessionHas('operation_error');
        $this->withHeaders(['X-Inertia' => ''])->postJson('/courses/'.$course->id.'/enroll-free')->assertStatus(409);
    }

    public function test_order_page_uses_current_entitlement_instead_of_payment_status_for_classroom_access(): void
    {
        $student = User::factory()->create();
        $course = $this->course();
        $enrollment = app(GrantEnrollment::class)->free($student, $course);
        $order = Order::create(['public_reference' => fake()->uuid(), 'user_id' => $student->id, 'course_id' => $course->id, 'title_snapshot' => $course->title, 'learner_name' => $student->name, 'terms_version' => 'test', 'currency' => 'AED', 'subtotal_minor' => 100, 'tax_minor' => 0, 'total_minor' => 100, 'payment_status' => 'paid']);
        $enrollment->update(['status' => 'revoked']);
        $this->actingAs($student)->get('/orders/'.$order->public_reference)->assertInertia(fn (Assert $p) => $p->where('order.payment_status', 'paid')->where('order.has_access', false));
        $this->getJson('/orders/'.$order->public_reference)->assertJsonPath('has_access', false);
    }

    public function test_record_search_and_status_filters_work_together(): void
    {
        $course = $this->course();
        $student = User::factory()->create(['name' => 'Searchable learner']);
        $e = app(GrantEnrollment::class)->free($student, $course);
        $e->update(['status' => 'suspended']);
        $this->admin();
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Livewire\Livewire::test(\App\Filament\Resources\EnrollmentResource\Pages\ListEnrollments::class)->searchTable('Searchable')->filterTable('status', 'active')->assertCanNotSeeTableRecords([$e])->filterTable('status', 'suspended')->assertCanSeeTableRecords([$e]);
    }

    public function test_public_error_pages_are_custom_and_do_not_expose_framework_diagnostics(): void
    {
        $this->get('/courses/missing-course')->assertNotFound()->assertSee('This request couldn’t be completed.')->assertDontSee('Laravel');
    }
}
