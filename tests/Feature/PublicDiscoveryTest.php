<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unavailable_courses_are_excluded_from_discovery_and_direct_public_access(): void
    {
        $unavailable = [
            Course::factory()->create(['title' => 'Private draft fixture', 'status' => 'draft', 'sales_visible' => true]),
            Course::factory()->create(['title' => 'Archived fixture', 'status' => 'archived', 'sales_visible' => true]),
            Course::factory()->create(['title' => 'Hidden sales fixture', 'status' => 'published', 'sales_visible' => false]),
            Course::factory()->create(['title' => 'Taken down fixture', 'status' => 'published', 'sales_visible' => true, 'takedown_reason' => 'Private internal reason']),
        ];
        $visible = Course::factory()->create(['title' => 'Visible course fixture', 'status' => 'published', 'sales_visible' => true]);

        foreach (['/', '/courses', '/sitemap.xml'] as $url) {
            $response = $this->get($url)->assertOk();
            $response->assertSee('/courses/'.$visible->slug, false);

            foreach ($unavailable as $course) {
                $response->assertDontSee($course->title)->assertDontSee('/courses/'.$course->slug, false);
            }

            $response->assertDontSee('Private internal reason');
        }

        foreach ($unavailable as $course) {
            $this->get('/courses/'.$course->slug)->assertNotFound();
        }
    }

    public function test_public_curriculum_previews_exclude_drafts_and_protected_lesson_data(): void
    {
        $course = Course::factory()->create(['status' => 'published', 'sales_visible' => true, 'format' => 'hybrid']);
        $module = $course->modules()->create(['title' => 'Public chapter fixture', 'position' => 1]);
        $module->lessons()->create([
            'title' => 'Unreleased lesson fixture',
            'position' => 1,
            'type' => 'text',
            'required' => false,
            'published' => false,
            'body' => 'PRIVATE-DRAFT-BODY',
        ]);
        $lesson = $module->lessons()->create([
            'title' => 'Published lesson fixture',
            'position' => 2,
            'type' => 'live',
            'required' => true,
            'published' => true,
            'body' => 'PRIVATE-ENROLLED-LESSON-BODY',
            'video_uid' => 'PRIVATE-STREAM-VIDEO-IDENTIFIER',
        ]);
        $lesson->session()->create([
            'provider' => 'Fixture meeting provider',
            'join_url' => 'https://example.test/private-room?passcode=PRIVATE-MEETING-PASSCODE',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'timezone' => 'Asia/Dubai',
            'status' => 'scheduled',
        ]);

        foreach (['/', '/courses/'.$course->slug] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('Published lesson fixture')
                ->assertDontSee('Unreleased lesson fixture')
                ->assertDontSee('PRIVATE-DRAFT-BODY')
                ->assertDontSee('PRIVATE-ENROLLED-LESSON-BODY')
                ->assertDontSee('PRIVATE-STREAM-VIDEO-IDENTIFIER')
                ->assertDontSee('example.test/private-room')
                ->assertDontSee('PRIVATE-MEETING-PASSCODE');
        }
    }

    public function test_closing_enrollment_does_not_hide_a_published_live_course(): void
    {
        $course = Course::factory()->create([
            'title' => 'Enrollment closed fixture',
            'status' => 'published',
            'sales_visible' => true,
            'format' => 'live',
            'enrollment_deadline' => now()->subDay(),
        ]);

        foreach (['/', '/courses', '/courses/'.$course->slug] as $url) {
            $this->get($url)->assertOk()->assertSee($course->title);
        }

        $this->get('/sitemap.xml')->assertOk()->assertSee('/courses/'.$course->slug, false);
    }
}
