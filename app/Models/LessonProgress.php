<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonInterface|null $completed_at
 */
class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected $casts = ['completed_at' => 'datetime'];
}
