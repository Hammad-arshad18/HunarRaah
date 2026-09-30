<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string,mixed> $payload
 */
class WebhookEvent extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected $casts = ['payload' => 'encrypted:array'];
}
