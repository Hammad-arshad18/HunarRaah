<?php

namespace Tests\Feature;

use App\Actions\GrantEnrollment;
use App\Filament\Resources\CourseResource\Pages\CreateCourse;
use App\Filament\Resources\CourseResource\Pages\EditCourse;
use App\Filament\Resources\EnrollmentResource\Pages\ListEnrollments;
use App\Filament\Resources\LessonResource\Pages\ListLessons;
use App\Filament\Resources\ModuleResource\Pages\ListModules;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Filament\Support\AdminWorkflows;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\AdministratorSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'two_factor_secret' => encrypt('fixture'), 'two_factor_confirmed_at' => now()]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $admin;
    }

    private function courseData(): array
    {
        return ['title' => 'Native teaching course', 'slug' => 'native-teaching-course', 'summary' => 'A clear outcome', 'description' => 'Meaningful teaching content', 'outcomes' => ['Build a useful artifact'], 'prerequisites' => '', 'target_audience' => '', 'level' => 'Beginner', 'duration_minutes' => 60, 'format' => 'recorded', 'instructor_name' => 'Development instructor', 'instructor_bio' => 'Development fixture biography', 'price_minor' => 0, 'currency' => 'AED', 'certificate_enabled' => true, 'recording_alternative' => false, 'accessible_content_confirmed' => true, 'access_policy' => 'Ongoing access', 'completion_policy' => 'Every required lesson', 'enrollment_deadline' => null];
    }

    private function course(): Course
    {
        return Course::create([...$this->courseData(), 'status' => 'published', 'sales_visible' => true]);
    }

    public function test_admin_entry_uses_existing_fortify_login_and_requires_role_mfa_and_recent_password(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/login')->assertRedirect('/login');
        $student = User::factory()->create();
        $this->actingAs($student)->get('/admin')->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/setup');
        $admin->forceFill(['two_factor_secret' => encrypt('fixture'), 'two_factor_confirmed_at' => now()])->save();
        $this->get('/admin')->assertRedirect('/user/confirm-password');
        $this->withSession(['auth.password_confirmed_at' => time()])->get('/admin')->assertOk()->assertSee('Your teaching desk')->assertDontSee('ui-avatars.com')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_all_administration_sections_are_native_filament_pages(): void
    {
        $this->administrator();
        foreach (['courses', 'courses/create', 'modules', 'lessons', 'students', 'enrollments', 'orders', 'certificates', 'audit-logs', 'failed-jobs'] as $path) {
            $this->get('/admin/'.$path)->assertOk()->assertSee('livewire')->assertDontSee('data-page=');
        }
    }

    public function test_native_course_create_edit_and_publish_keep_shared_validation(): void
    {
        $this->administrator();
        Livewire::test(CreateCourse::class)->fillForm($this->courseData())->call('create')->assertHasNoFormErrors();
        $course = Course::firstOrFail();
        $this->assertSame('draft', $course->status);
        Livewire::test(EditCourse::class, ['record' => $course->id])->callAction('publish')->assertNotified('Check the course or lesson');
        $this->assertSame('draft', $course->fresh()->status);
        $module = $course->modules()->create(['title' => 'Start here', 'position' => 1]);
        $module->lessons()->create(['title' => 'Read', 'body' => 'A real reading', 'position' => 1, 'type' => 'text', 'required' => true, 'published' => true]);
        Livewire::test(EditCourse::class, ['record' => $course->id])->fillForm(['title' => 'Corrected course title'])->call('save')->assertHasNoFormErrors()->callAction('publish');
        $this->assertSame('published', $course->fresh()->status);
        $this->assertSame('Corrected course title', $course->fresh()->title);
        $this->assertDatabaseHas('audit_logs', ['action' => 'course.published']);
    }

    public function test_native_course_completion_policy_cannot_change_after_enrollment(): void
    {
        $this->administrator();
        $course = $this->course();
        app(GrantEnrollment::class)->free(User::factory()->create(), $course);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        AdminWorkflows::saveCourse([...$this->courseData(), 'completion_policy' => 'Changed rules'], $course);
    }

    public function test_native_chapter_and_lesson_actions_enforce_curriculum_lock(): void
    {
        $this->administrator();
        $course = $this->course();
        Livewire::test(ListModules::class)->callTableAction('create', data: ['course_id' => $course->id, 'title' => 'Native chapter', 'position' => 1])->assertHasNoTableActionErrors();
        $module = $course->modules()->firstOrFail();
        Livewire::test(ListLessons::class)->callTableAction('create', data: ['module_id' => $module->id, 'title' => 'Native lesson', 'summary' => '', 'body' => 'Protected reading', 'type' => 'text', 'required' => true, 'published' => true, 'position' => 1])->assertHasNoTableActionErrors();
        $lesson = $module->lessons()->firstOrFail();
        app(GrantEnrollment::class)->free(User::factory()->create(), $course);
        Livewire::test(ListLessons::class)->callTableAction('edit', $lesson, data: [...$lesson->only('title', 'summary', 'body', 'type', 'required', 'published', 'position'), 'type' => 'video', 'reason' => 'Attempted structural revision'])->assertHasErrors(['reason']);
        $this->assertSame('text', $lesson->fresh()->type);
        Livewire::test(ListLessons::class)->callTableAction('remove', $lesson, data: ['reason' => 'Attempted deletion'])->assertHasErrors(['reason']);
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }

    public function test_native_complimentary_grant_corrections_and_restrictions_are_audited(): void
    {
        Queue::fake();
        $this->administrator();
        $course = $this->course();
        $student = User::factory()->create();
        $module = $course->modules()->create(['title' => 'Chapter', 'position' => 1]);
        $lesson = $module->lessons()->create(['title' => 'Reading', 'body' => 'Real content', 'type' => 'text', 'position' => 1, 'required' => true, 'published' => true]);
        Livewire::test(ListEnrollments::class)->callTableAction('grant', data: ['user_id' => $student->id, 'course_id' => $course->id, 'reason' => 'Owner approved scholarship'])->assertHasNoTableActionErrors();
        $enrollment = $course->enrollments()->firstOrFail();
        $this->assertDatabaseCount('orders', 0);
        Livewire::test(ListEnrollments::class)->callTableAction('completion', $enrollment, data: ['lesson_id' => $lesson->id, 'source' => 'complete', 'reason' => 'Reviewed material'])->assertHasNoTableActionErrors();
        $this->assertNotNull($enrollment->fresh()->completed_at);
        Livewire::test(ListEnrollments::class)->callTableAction('restriction', $enrollment, data: ['status' => 'suspended', 'reason' => 'Owner review'])->assertHasNoTableActionErrors();
        $this->assertSame('Owner review', $enrollment->fresh()->admin_restriction);
        $this->assertDatabaseHas('audit_logs', ['action' => 'enrollment.complimentary', 'reason' => 'Owner approved scholarship']);
    }

    public function test_native_student_suspend_and_restore_use_current_record_state(): void
    {
        $this->administrator();
        $student = User::factory()->create();
        $component = Livewire::test(ListStudents::class);
        $component->callTableAction('suspension', $student, data: ['reason' => 'Review'])->assertHasNoTableActionErrors();
        $this->assertNotNull($student->fresh()->suspended_at);
        $component->callTableAction('suspension', $student, data: ['reason' => 'Review resolved'])->assertHasNoTableActionErrors();
        $this->assertNull($student->fresh()->suspended_at);
    }

    public function test_workflow_checks_reject_direct_actions_after_role_change_or_password_timeout(): void
    {
        $admin = $this->administrator();
        $admin->forceFill(['role' => 'student'])->save();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        AdminWorkflows::request([]);
    }

    public function test_workflow_rejects_stale_password_confirmation(): void
    {
        $this->administrator();
        session()->put('auth.password_confirmed_at', time() - 10801);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        AdminWorkflows::request([]);
    }

    public function test_owner_requested_seeder_creates_one_admin_without_resetting_password(): void
    {
        $this->seed(AdministratorSeeder::class);
        $admin = User::where('email', 'admin@mirzalearning.com')->firstOrFail();
        $hash = $admin->password;
        $this->seed(AdministratorSeeder::class);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_bootstrap_seeder_is_disabled_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(\RuntimeException::class);
        app(AdministratorSeeder::class)->run();
    }

 public function test_native_certificate_issue_reissue_and_revocation_preserve_history(): void
 {
  Queue::fake();$this->administrator();$course=$this->course();$student=User::factory()->create();
  $module=$course->modules()->create(['title'=>'Chapter','position'=>1]);
  $lesson=$module->lessons()->create(['title'=>'Reading','body'=>'Content','type'=>'text','position'=>1,'required'=>true,'published'=>true]);
  $enrollment=app(GrantEnrollment::class)->free($student,$course);
  app(\App\Actions\CompleteLesson::class)->execute($student,$lesson);
  $enrollment->refresh();
  Livewire::test(ListEnrollments::class)->callTableAction('certificate',$enrollment,data:['reason'=>'Reviewed completion'])->assertHasNoTableActionErrors();
  $certificate=\App\Models\Certificate::firstOrFail();
  Livewire::test(\App\Filament\Resources\CertificateResource\Pages\ListCertificates::class)->callTableAction('reissue',$certificate,data:['learner_name'=>'Corrected learner name','reason'=>'Reviewed name correction'])->assertHasNoTableActionErrors();
  $this->assertSame('superseded',$certificate->fresh()->status);
  $new=\App\Models\Certificate::where('current_enrollment_id',$enrollment->id)->firstOrFail();
  $this->assertSame('Corrected learner name',$new->learner_name);
  $this->assertNull($new->public_enabled_at);
  Livewire::test(\App\Filament\Resources\CertificateResource\Pages\ListCertificates::class)->callTableAction('revoke',$new,data:['reason'=>'Explicit credential review'])->assertHasNoTableActionErrors();
  $this->assertSame('revoked',$new->fresh()->status);
  $this->assertDatabaseCount('certificates',2);
 }
 public function test_native_recording_attachment_uses_verified_provider_state(): void
 {
  $this->administrator();$course=$this->course();$module=$course->modules()->create(['title'=>'Chapter','position'=>1]);
  $lesson=$module->lessons()->create(['title'=>'Recording','type'=>'video','position'=>1,'required'=>true,'published'=>false]);
  $uid=str_repeat('a',32);
  $this->mock(\App\Services\StreamGateway::class,fn($mock)=>$mock->shouldReceive('inspect')->once()->with($uid)->andReturn(['uid'=>$uid,'requireSignedURLs'=>true,'allowedOrigins'=>['localhost'],'readyToStream'=>true,'duration'=>90]));
  Livewire::test(ListLessons::class)->callTableAction('recording',$lesson,data:['video_uid'=>$uid,'reason'=>'Attach accessible recording'])->assertHasNoTableActionErrors();
  $this->assertSame('ready',$lesson->fresh()->video_status);
  $this->assertDatabaseHas('audit_logs',['action'=>'video.attached']);
 }
 public function test_production_admin_html_has_nonce_on_every_inline_script_and_keeps_public_csp_strict(): void
 {
  $this->administrator();$this->app['env']='production';
  $response=$this->get('/admin')->assertOk();
  $this->assertStringContainsString("'unsafe-eval'",$response->headers->get('Content-Security-Policy'));
  $dom=new \DOMDocument;@$dom->loadHTML($response->getContent());
  foreach($dom->getElementsByTagName('script') as $script){
   if(!$script->hasAttribute('src'))$this->assertNotEmpty($script->getAttribute('nonce'), 'Missing nonce: '.mb_substr($script->textContent, 0, 100));
  }
  $public=$this->get('/')->assertOk();
  $this->assertStringNotContainsString("'unsafe-eval'",$public->headers->get('Content-Security-Policy'));
 }
 public function test_password_confirmation_navigates_out_of_inertia_into_filament(): void
 {
  $this->administrator();
  $this->withSession(['url.intended'=>url('/admin')])->withHeaders(['X-Inertia'=>'true'])->post('/user/confirm-password',['password'=>'password'])->assertStatus(409)->assertHeader('X-Inertia-Location',url('/admin'));
 }
}
