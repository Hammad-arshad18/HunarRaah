<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property array<int,string> $outcomes
 * @property CarbonInterface|null $enrollment_deadline
 */
class Course extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected $casts = ['outcomes' => 'array', 'sales_visible' => 'boolean', 'certificate_enabled' => 'boolean', 'recording_alternative' => 'boolean', 'accessible_content_confirmed' => 'boolean', 'enrollment_deadline' => 'datetime'];

    /** @return HasMany<Module> */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasManyThrough<Lesson> */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    /** @return HasMany<Enrollment> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function acceptsEnrollment(): bool
    {
        return $this->status === 'published' && $this->sales_visible && ! $this->takedown_reason
            && (! $this->enrollment_deadline || $this->enrollment_deadline->isFuture());
    }
}
