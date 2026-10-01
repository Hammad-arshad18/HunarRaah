<?php

namespace App\Filament\Support;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class StudioResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && $user->canAccessPanel(\Filament\Facades\Filament::getCurrentPanel());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
