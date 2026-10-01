<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $access_ends_at
 */
class Enrollment extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected $casts = ['access_ends_at' => 'datetime', 'granted_at' => 'datetime', 'completed_at' => 'datetime'];

    /** @return BelongsTo<Course, Enrollment> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, Enrollment> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Certificate> */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /** @return HasMany<LessonProgress> */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
