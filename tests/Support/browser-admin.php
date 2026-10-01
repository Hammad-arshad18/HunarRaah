<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! app()->environment(['local', 'testing']) || config('mail.default') !== 'log' || ! str_contains(config('database.connections.sqlite.database'), 'browser.sqlite') || config('database.default') !== 'sqlite') {
    throw new RuntimeException('Browser fixtures require the isolated browser.sqlite database and log mail.');
}
if (($argv[1] ?? '') === 'otp') {
    echo (new PragmaRX\Google2FA\Google2FA)->getCurrentOtp($argv[2]);
    exit;
}
$password = bin2hex(random_bytes(16));
$secret = (new PragmaRX\Google2FA\Google2FA)->generateSecretKey();
$user = App\Models\User::forceCreate(['name' => 'Development Browser Administrator', 'email' => 'admin-'.bin2hex(random_bytes(5)).'@example.test', 'email_verified_at' => now(), 'password' => Illuminate\Support\Facades\Hash::make($password), 'role' => 'admin', 'two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => encrypt(json_encode([bin2hex(random_bytes(10))]))]);
$suffix = bin2hex(random_bytes(5));
$student = App\Models\User::forceCreate(['name' => 'Development Browser Learner '.$suffix, 'email' => 'learner-'.$suffix.'@example.test', 'email_verified_at' => now(), 'password' => Illuminate\Support\Facades\Hash::make($password), 'role' => 'student']);
$course = App\Models\Course::where('slug', 'sample-course-1')->firstOrFail();
echo json_encode(['student' => ['id' => $student->id, 'name' => $student->name, 'email' => $student->email, 'password' => $password], 'course_id' => $course->id, 'lesson_ids' => $course->lessons()->where('required', true)->where('published', true)->pluck('lessons.id'), 'email' => $user->email, 'password' => $password, 'secret' => $secret]);
