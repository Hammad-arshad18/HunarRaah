<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => 'Development sample: '.fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'summary' => 'Explicit development fixture, not a production teaching claim.',
            'description' => 'Development fixture content.',
            'outcomes' => ['Practice a sample skill'],
            'format' => 'recorded',
            'instructor_name' => 'Development sample instructor',
            'instructor_bio' => 'Fictional development fixture.',
            'currency' => 'AED',
            'price_minor' => 0,
            'status' => 'draft',
            'sales_visible' => false,
            'completion_policy' => 'Complete every required lesson.',
            'access_policy' => 'Ongoing access unless revoked under the published policy.',
        ];
    }
}
