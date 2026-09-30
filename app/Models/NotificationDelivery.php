<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<User, NotificationDelivery> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
