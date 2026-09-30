<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $guarded = ['id'];

    /** @return HasOne<PaymentAttempt> */
    public function attempt(): HasOne
    {
        return $this->hasOne(PaymentAttempt::class);
    }

    /** @return BelongsTo<User, Order> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Course, Order> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
