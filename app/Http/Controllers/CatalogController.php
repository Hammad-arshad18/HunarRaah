<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\Entitlement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home(): View
    {
        return view('public.home', ['courses' => $this->visible()->with(['modules.lessons' => fn ($q) => $q->where('published', true)->select('id', 'module_id', 'title', 'type', 'position')])->limit(3)->get()]);
    }

    /** @return Builder<Course> */
    private function visible(): Builder
    {
        return Course::where('status', 'published')->where('sales_visible', true)->whereNull('takedown_reason');
    }

    public function index(Request $request): View
    {
        $filter = $request->validate(['q' => 'nullable|string|max:100', 'format' => 'nullable|in:recorded,live,hybrid']);
        $courses = $this->visible()->when($filter['q'] ?? null, fn ($q, $term) => $q->where('title', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->when($filter['format'] ?? null, fn ($q, $format) => $q->where('format', $format))->orderBy('title')->paginate(12)->withQueryString();

        return view('public.catalog', compact('courses'));
    }

    public function show(Request $request, string $slug): View
    {
        $course = $this->visible()->where('slug', $slug)->firstOrFail();
        $course->load(['modules.lessons' => fn ($q) => $q->where('published', true)->select('id', 'module_id', 'title', 'summary', 'type', 'required', 'position'), 'modules.lessons.session' => fn ($q) => $q->select('id', 'lesson_id', 'starts_at', 'ends_at', 'timezone', 'status', 'message')]);
        $enrolled = $request->user() ? app(Entitlement::class)->forCourse($request->user(), $course) : null;

        return view('public.course', compact('course', 'enrolled'));
    }
}
