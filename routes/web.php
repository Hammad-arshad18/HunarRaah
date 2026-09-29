<?php

use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\AdminOperationsController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseImageController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PaymentController;
use App\Models\Course;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/sitemap.xml', fn () => response()->view('public.sitemap', ['courses' => Course::where('status', 'published')->where('sales_visible', true)->whereNull('takedown_reason')->select('slug')->get()])->header('Content-Type', 'application/xml'));
Route::get('/courses', [CatalogController::class, 'index'])->name('courses.index');
Route::get('/courses/{slug}', [CatalogController::class, 'show'])->name('courses.show');
Route::post('/webhooks/stripe', [PaymentController::class, 'webhook']);
Route::get('/certificates/verify/{token}', [CertificateController::class, 'verify'])->middleware('throttle:30,1');
foreach (['terms', 'privacy', 'refund-policy', 'support'] as $page) {
    Route::view('/'.$page, 'public.policy', ['page' => $page]);
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [LearningController::class, 'dashboard'])->name('dashboard');
    Route::post('/courses/{course}/checkout', [PaymentController::class, 'checkout'])->middleware('throttle:10,1');
    Route::get('/orders/{reference}', [PaymentController::class, 'show']);
    Route::post('/lessons/{lesson}/playback-token', [MediaController::class, 'playback'])->middleware('throttle:20,1');
    Route::post('/live-sessions/{session}/join', [MediaController::class, 'join'])->middleware('throttle:20,1');
    Route::post('/enrollments/{enrollment}/certificate', [CertificateController::class, 'issue'])->middleware('throttle:10,1');
    Route::get('/certificates/{credential}', [CertificateController::class, 'show']);
    Route::get('/certificates/{credential}/download', [CertificateController::class, 'download']);
    Route::patch('/certificates/{credential}/sharing', [CertificateController::class, 'sharing']);
    Route::get('/learn/{course}/{lesson?}', [LearningController::class, 'classroom']);
    Route::post('/courses/{course}/enroll-free', [LearningController::class, 'enroll'])->middleware('throttle:10,1');
    Route::post('/lessons/{lesson}/complete', [LearningController::class, 'complete'])->middleware('throttle:30,1');
    Route::post('/lessons/{lesson}/progress', [LearningController::class, 'position'])->middleware('throttle:60,1');
    Route::prefix('admin')->middleware(['admin', 'password.confirm'])->group(function () {
        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::get('/operations', [AdminOperationsController::class, 'index']);
        Route::post('/students/{user}/suspension', [AdminOperationsController::class, 'suspend']);
        Route::post('/courses/{course}/grant', [AdminOperationsController::class, 'grant']);
        Route::post('/enrollments/{enrollment}/restriction', [AdminOperationsController::class, 'restrict']);
        Route::post('/orders/{order}/reconcile', [AdminOperationsController::class, 'reconcile']);
        Route::post('/certificates/{certificate}/revoke', [AdminOperationsController::class, 'revoke']);
        Route::post('/certificates/{certificate}/reissue', [AdminOperationsController::class, 'reissue']);
        Route::post('/lessons/{lesson}/video', [MediaController::class, 'attach']);
        Route::get('/lessons/{lesson}/roster', [MediaController::class, 'roster']);
        Route::post('/lessons/{lesson}/session', [MediaController::class, 'schedule']);
        Route::post('/lessons/{lesson}/attendance/{enrollment}', [MediaController::class, 'attendance']);
        Route::get('/courses/{course}/edit', [AdminCourseController::class, 'edit']);
        Route::post('/courses', [AdminCourseController::class, 'store']);
        Route::post('/courses/{course}/image', [CourseImageController::class, 'store']);
        Route::put('/courses/{course}', [AdminCourseController::class, 'update']);
        Route::post('/courses/{course}/modules', [AdminCourseController::class, 'module']);
        Route::post('/courses/{course}/modules/{module}/lessons', [AdminCourseController::class, 'lesson']);
        Route::post('/courses/{course}/publish', [AdminCourseController::class, 'publish']);
        Route::post('/courses/{course}/archive', [AdminCourseController::class, 'archive']);
        Route::put('/courses/{course}/lessons/{lesson}', [AdminCourseController::class, 'reviseLesson']);
        Route::post('/courses/{course}/duplicate', [AdminCourseController::class, 'duplicate']);
        Route::get('/courses/{course}/preview', [AdminCourseController::class, 'preview']);
    });
});

require __DIR__.'/settings.php';
