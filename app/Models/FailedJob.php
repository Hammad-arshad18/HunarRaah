<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    public $timestamps = false;

    protected $guarded = ['*'];

    protected $hidden = ['payload', 'exception'];
}
