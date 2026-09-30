<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $last_reconciled_at
 */
class PaymentAttempt extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected $casts = ['expires_at' => 'datetime', 'last_reconciled_at' => 'datetime'];

    /** @return BelongsTo<Order, PaymentAttempt> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
