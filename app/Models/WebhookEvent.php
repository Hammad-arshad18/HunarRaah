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
    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }
}
