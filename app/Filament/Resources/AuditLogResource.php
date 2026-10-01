<?php

namespace App\Filament\Resources;

use App\Filament\Support\StudioResource;
use App\Models\AuditLog;
use Filament\Tables\Columns as C;
use Filament\Tables\Table;

class AuditLogResource extends StudioResource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Operations';

    public static function table(Table $table): Table
    {
        return $table->columns([C\TextColumn::make('created_at')->dateTime()->sortable(), C\TextColumn::make('actor.name')->label('Actor')->placeholder('System'), C\TextColumn::make('action')->searchable(), C\TextColumn::make('subject_type')->label('Record type'), C\TextColumn::make('subject_id')->label('Record ID'), C\TextColumn::make('reason')->wrap()])->defaultSort('id', 'desc')->emptyStateDescription('Audited administrator and system actions appear here.');
    }

    public static function getPages(): array
    {
        return ['index' => AuditLogResource\Pages\ListAuditLogs::route('/')];
    }
}
