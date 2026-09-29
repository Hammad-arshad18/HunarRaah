<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Development seeds are prohibited outside local/testing.');
        }
        foreach (['Build a clear project brief', 'An introduction to visual thinking', 'Write with purpose'] as $index => $title) {
            $course = Course::firstOrCreate(['slug' => 'sample-course-'.($index + 1)], ['title' => '[Development sample] '.$title, 'summary' => 'An explicitly labelled example for exploring the learning platform.', 'description' => '## A focused starting point\nThis sample is for development only. Replace it with owned teaching content before launch.', 'outcomes' => ['Structure an idea clearly', 'Identify your next practical step'], 'level' => 'Beginner', 'duration_minutes' => 35, 'format' => 'recorded', 'instructor_name' => 'Development sample instructor', 'instructor_bio' => 'Placeholder for testing only. No expertise claim is made.', 'price_minor' => 0, 'currency' => 'AED', 'status' => 'published', 'sales_visible' => true, 'accessible_content_confirmed' => true, 'access_policy' => 'Development access only.', 'completion_policy' => 'Self-attest completion of all required text lessons.']);
            $module = $course->modules()->firstOrCreate(['position' => 1], ['title' => 'Find the starting point']);
            foreach (['Understand the brief', 'Make your first outline'] as $position => $lesson) {
                $module->lessons()->firstOrCreate(['position' => $position + 1], ['title' => $lesson, 'type' => 'text', 'required' => true, 'published' => true, 'body' => "# Start with the question\n\nThis is development sample teaching content.\n\nWrite down what you already know. Then list the questions you need to answer.\n\n## Your next step\n\nPrepare a short outline before continuing."]);
            }
        }
    }
}
