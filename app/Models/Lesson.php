<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['video_uid'];

    /** @return array<string, string> */
    protected $casts = ['required' => 'boolean', 'published' => 'boolean'];

    /** @return BelongsTo<Module, Lesson> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /** @return HasOne<LiveSession> */
    public function session(): HasOne
    {
        return $this->hasOne(LiveSession::class);
    }
}
