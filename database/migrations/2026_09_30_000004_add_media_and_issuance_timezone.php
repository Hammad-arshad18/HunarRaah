<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $t) {
            $t->string('cover_path')->nullable();
            $t->string('instructor_photo_path')->nullable();
        });
        Schema::table('certificates', fn (Blueprint $t) => $t->string('issued_timezone')->default('Asia/Dubai'));
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $t) => $t->dropColumn(['cover_path', 'instructor_photo_path']));
        Schema::table('certificates', fn (Blueprint $t) => $t->dropColumn('issued_timezone'));
    }
};
