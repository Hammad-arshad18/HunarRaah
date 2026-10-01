<?php

namespace App\Filament\Resources;

use App\Filament\Support\AdminWorkflows;
use App\Filament\Support\Fields;
use App\Filament\Support\StudioResource;
use App\Models\FailedJob;
use Filament\Tables\Actions as A;
use Filament\Tables\Columns as C;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class FailedJobResource extends StudioResource
{
    protected static ?string $model = FailedJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Operations';

    public static function table(Table $table): Table
    {
        return $table->columns([C\TextColumn::make('uuid')->label('Job reference')->searchable(), C\TextColumn::make('connection'), C\TextColumn::make('queue'), C\TextColumn::make('failed_at')->dateTime()->sortable()])->actions([
            A\Action::make('retry')->label('Retry job')->modalDescription('Correct the provider or configuration issue before retrying. Inspect private application logs for diagnostics.')->form([Fields::reason()])->action(function (array $data, FailedJob $record) {
                AdminWorkflows::authorize();
                Artisan::call('queue:retry', ['id' => [$record->uuid]]);
                DB::table('audit_logs')->insert(['actor_id' => auth()->id(), 'action' => 'job.retried', 'subject_type' => 'failed_job', 'subject_id' => $record->id, 'reason' => $data['reason'], 'created_at' => now()]);
                \Filament\Notifications\Notification::make()->title('Job retry queued')->success()->send();
            }),
        ])->defaultSort('id', 'desc')->emptyStateHeading('No failed jobs')->emptyStateDescription('Provider failures and exhausted retries appear here for recovery.');
    }

    public static function getPages(): array
    {
        return ['index' => FailedJobResource\Pages\ListFailedJobs::route('/')];
    }
}
