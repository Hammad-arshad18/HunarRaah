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
    protected function casts(): array
    {
        return ['required' => 'boolean', 'published' => 'boolean'];
    }

    /** @return BelongsTo<Module, $this> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /** @return HasOne<LiveSession, $this> */
    public function session(): HasOne
    {
        return $this->hasOne(LiveSession::class);
    }
}
