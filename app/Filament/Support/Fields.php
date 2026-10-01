<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;

class Fields
{
    public static function reason(): Textarea
    {
        return Textarea::make('reason')->label('Reason for this change')->required()->maxLength(1000)->rows(2)->columnSpanFull();
    }
}
