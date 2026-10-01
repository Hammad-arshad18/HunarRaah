<?php

namespace App\Filament\Resources;

use App\Filament\Support\AdminWorkflows as Workflow;
use App\Filament\Support\Fields;
use App\Filament\Support\StudioResource;
use App\Http\Controllers\AdminOperationsController;
use App\Models\User;
use Filament\Forms\Components as F;
use Filament\Tables\Actions as A;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends StudioResource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'students';

    protected static ?string $navigationLabel = 'Students';

    protected static ?string $modelLabel = 'student';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Student records';

    /** @return Builder<User> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'student');
    }

    public static function table(Table $table): Table
    {
        return $table->columns([C\TextColumn::make('name')->searchable()->sortable(), C\TextColumn::make('email')->searchable(), C\TextColumn::make('email_verified_at')->label('Verified')->dateTime()->placeholder('Awaiting verification'), C\TextColumn::make('suspended_at')->label('Suspended')->dateTime()->placeholder('Active')])->filters([TernaryFilter::make('suspended_at')->native(false)->label('Suspended')->nullable()])->actions([
            A\Action::make('suspension')->label(fn (User $record) => $record->suspended_at ? 'Restore account' : 'Suspend account')->color('warning')->form([Fields::reason()])->action(fn (array $data, User $record) => Workflow::run(AdminOperationsController::class, 'suspend', [...$data, 'suspended' => ! $record->suspended_at], $record)),
            A\Action::make('email')->label('Correct email')->fillForm(fn (User $record) => ['email' => $record->email])->modalDescription('Requires new email verification and ends current sessions. No issued certificate is changed.')->form([F\TextInput::make('email')->email()->required()->maxLength(255), Fields::reason()])->action(fn (array $data, User $record) => Workflow::run(AdminOperationsController::class, 'email', $data, $record)),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => StudentResource\Pages\ListStudents::route('/')];
    }
}
