<?php

use App\Actions\CompleteLesson;
use App\Actions\GrantEnrollment;
use App\Actions\IssueCertificate;
use App\Actions\ReconcileOrderPayment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;


// Standalone destructive harness restricted to the isolated test database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.connections.mysql.database') !== 'studio_test' || config('database.default') !== 'mysql') {
    throw new RuntimeException('Isolated MySQL test database required');
}

$mode = $argv[1] ?? '';
if ($mode === 'prepare') {
    Artisan::call('migrate:fresh', ['--force' => true]);
    $u = User::factory()->create(['email' => 'concurrency@example.test']);
    $c = Course::create(['title' => 'Concurrent fixture', 'slug' => 'concurrent-fixture', 'summary' => 'Fixture', 'description' => 'Fixture', 'outcomes' => ['Fixture'], 'format' => 'recorded', 'instructor_name' => 'Fixture', 'instructor_bio' => 'Fixture', 'currency' => 'AED', 'price_minor' => 0, 'status' => 'published', 'sales_visible' => true, 'access_policy' => 'Fixture', 'completion_policy' => 'Fixture']);
    $m = $c->modules()->create(['title' => 'Fixture', 'position' => 1]);
    $m->lessons()->create(['title' => 'Fixture', 'type' => 'text', 'position' => 1, 'published' => true, 'required' => true, 'body' => 'Fixture']);
    $o = Order::create(['public_reference' => 'concurrent-order', 'user_id' => $u->id, 'course_id' => $c->id, 'title_snapshot' => 'Fixture', 'learner_name' => $u->name, 'terms_version' => 'test', 'currency' => 'AED', 'subtotal_minor' => 49900, 'tax_minor' => 0, 'total_minor' => 49900]);
    $o->attempt()->create(['session_id' => 'cs_concurrent', 'idempotency_key' => 'concurrent-key']);
    echo "prepared\n";
} elseif ($mode === 'enroll') {
    app(GrantEnrollment::class)->free(User::firstOrFail(), Course::firstOrFail());
    echo "enrolled\n";
} elseif ($mode === 'prepare-payment') {
    Enrollment::query()->delete();
    Course::firstOrFail()->update(['price_minor' => 49900]);
    echo "prepared-payment\n";
} elseif ($mode === 'pay') {
    $o = Order::firstOrFail();
    app(ReconcileOrderPayment::class)->execute($o->attempt, ['id' => 'cs_concurrent', 'metadata' => ['order_reference' => 'concurrent-order'], 'client_reference_id' => 'concurrent-order', 'amount_total' => 49900, 'currency' => 'aed', 'livemode' => false, 'payment_status' => 'paid', 'status' => 'complete', 'refunded_minor' => 0, 'dispute_status' => 'none']);
    echo "fulfilled\n";
} elseif ($mode === 'complete') {
    app(CompleteLesson::class)->execute(User::firstOrFail(), Course::firstOrFail()->lessons()->firstOrFail());
    echo "completed\n";
} elseif ($mode === 'certificate') {
    app(IssueCertificate::class)->execute(User::firstOrFail(), Enrollment::firstOrFail());
    echo "issued\n";
} elseif ($mode === 'check') {
    if (Enrollment::count() !== 1) {
        throw new RuntimeException('Enrollment count mismatch');
    }if (($argv[2] ?? '') === 'certificate' && Certificate::count() !== 1) {
        throw new RuntimeException('Credential count mismatch');
    }echo "unique constraints and fulfillment verified\n";
} else {
    throw new RuntimeException('Unknown mode');
}
