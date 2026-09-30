<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonInterface $issued_at
 * @property CarbonInterface|null $public_enabled_at
 * @property string $issued_timezone
 */
class Certificate extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['private_pdf_path', 'verification_token'];

    /** @return array<string, string> */
    protected $casts = ['issued_at' => 'datetime', 'public_enabled_at' => 'datetime'];

    /** @return BelongsTo<Enrollment, Certificate> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
