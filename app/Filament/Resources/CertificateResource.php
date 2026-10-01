<?php

namespace App\Filament\Resources;

use App\Filament\Support\AdminWorkflows as Workflow;
use App\Filament\Support\Fields;
use App\Filament\Support\StudioResource;
use App\Http\Controllers\AdminOperationsController;
use App\Jobs\GenerateCertificate;
use App\Models\Certificate;
use Filament\Forms\Components as F;
use Filament\Tables\Actions as A;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class CertificateResource extends StudioResource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Student records';

    public static function table(Table $table): Table
    {
        return $table->columns([C\TextColumn::make('credential_id')->searchable()->copyable(), C\TextColumn::make('learner_name')->searchable(), C\TextColumn::make('course_title')->searchable()->wrap(), C\TextColumn::make('status')->badge(), C\TextColumn::make('generation_status')->label('PDF')->badge(), C\TextColumn::make('issued_at')->date()->sortable(), C\TextColumn::make('public_enabled_at')->label('Public sharing')->dateTime()->placeholder('Private')])->filters([SelectFilter::make('status')->native(false)->options(['valid' => 'Valid', 'suspended' => 'Suspended', 'revoked' => 'Revoked', 'superseded' => 'Superseded']), SelectFilter::make('generation_status')->native(false)->options(['pending' => 'Pending', 'ready' => 'Ready', 'failed' => 'Failed'])])->actions([
            A\ActionGroup::make([
            A\Action::make('download')->visible(fn (Certificate $record) => $record->generation_status === 'ready')->url(fn (Certificate $record) => '/admin/certificates/'.$record->id.'/download')->openUrlInNewTab(),
            A\Action::make('reissue')->label('Correct name & reissue')->visible(fn (Certificate $record) => $record->status === 'valid' && $record->current_enrollment_id)->fillForm(fn (Certificate $record) => ['learner_name' => $record->learner_name])->form([F\TextInput::make('learner_name')->required()->maxLength(255), Fields::reason()])->action(fn (array $data, Certificate $record) => Workflow::run(AdminOperationsController::class, 'reissue', $data, $record)),
            A\Action::make('revoke')->color('danger')->visible(fn (Certificate $record) => $record->status !== 'revoked')->form([Fields::reason()])->requiresConfirmation()->action(fn (array $data, Certificate $record) => Workflow::run(AdminOperationsController::class, 'revoke', $data, $record)),
            A\Action::make('retry')->label('Retry PDF')->visible(fn (Certificate $record) => $record->generation_status === 'failed')->requiresConfirmation()->action(function (Certificate $record) {
                Workflow::authorize();
                DB::transaction(function () use ($record) {
                    $locked = Certificate::whereKey($record->id)->lockForUpdate()->firstOrFail();
                    if ($locked->generation_status !== 'failed') {
                        return;
                    }$locked->update(['generation_status' => 'pending']);
                    GenerateCertificate::dispatch($locked->id)->afterCommit();
                    DB::table('audit_logs')->insert(['actor_id' => auth()->id(), 'action' => 'certificate.pdf_retry', 'subject_type' => 'certificate', 'subject_id' => $record->id, 'reason' => 'Administrator PDF retry', 'created_at' => now()]);
                });
                \Filament\Notifications\Notification::make()->title('PDF retry queued')->success()->send();
            }),
        
            ])->label('Manage')->icon('heroicon-o-ellipsis-horizontal')->button()->color('gray'),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => CertificateResource\Pages\ListCertificates::route('/')];
    }
}
