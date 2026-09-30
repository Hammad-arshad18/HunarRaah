<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonInterface $starts_at
 * @property CarbonInterface $ends_at
 */
class LiveSession extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['join_url'];

    /** @return array<string, string> */
    protected $casts = ['join_url' => 'encrypted', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    /** @return BelongsTo<Lesson, LiveSession> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
