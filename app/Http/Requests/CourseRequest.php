<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin' && $this->user()->two_factor_confirmed_at !== null;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200', 'slug' => ['required', 'alpha_dash:ascii', 'max:200', Rule::unique('courses')->ignore($this->route('course'))],
            'summary' => 'required|string|max:500', 'description' => 'required|string|max:30000',
            'outcomes' => 'required|array|min:1|max:20', 'outcomes.*' => 'required|string|max:300',
            'prerequisites' => 'nullable|string|max:3000', 'target_audience' => 'nullable|string|max:3000',
            'level' => 'required|string|max:80', 'duration_minutes' => 'required|integer|min:0|max:100000',
            'format' => 'required|in:recorded,live,hybrid', 'instructor_name' => 'required|string|max:200', 'instructor_bio' => 'required|string|max:5000',
            'price_minor' => 'required|integer|min:0|max:100000000', 'currency' => ['required', Rule::in([config('platform.currency')])],
            'enrollment_deadline' => 'nullable|date', 'certificate_enabled' => 'required|boolean', 'recording_alternative' => 'required|boolean',
            'accessible_content_confirmed' => 'required|boolean', 'access_policy' => 'required|string|max:5000', 'completion_policy' => 'required|string|max:5000',
        ];
    }
}
