<?php

namespace App\Filament\Resources;

use App\Filament\Support\AdminWorkflows as Workflow;
use App\Filament\Support\Fields;
use App\Filament\Support\StudioResource;
use App\Http\Controllers\AdminCourseController;
use App\Models\Course;
use App\Models\Module;
use Filament\Forms\Components as F;
use Filament\Tables\Actions as A;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ModuleResource extends StudioResource
{
    protected static ?string $model = Module::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Curriculum';

    protected static ?string $navigationLabel = 'Chapters';

    protected static ?string $modelLabel = 'chapter';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table->columns([C\TextColumn::make('course.title')->searchable()->wrap(), C\TextColumn::make('position')->label('Chapter')->sortable(), C\TextColumn::make('title')->searchable(), C\TextColumn::make('lessons_count')->counts('lessons')->label('Lessons')])->filters([SelectFilter::make('course_id')->native(false)->label('Course')->options(fn () => Course::pluck('title', 'id'))])->headerActions([
            A\Action::make('create')->label('Add chapter')->form([F\Select::make('course_id')->label('Course')->options(fn () => Course::pluck('title', 'id'))->native(false)->required(), F\TextInput::make('title')->required()->maxLength(200), F\TextInput::make('position')->label('Chapter number')->integer()->minValue(1)->default(1)->required()])->action(fn (array $data) => Workflow::run(AdminCourseController::class, 'module', $data, Course::findOrFail((int) $data['course_id']))),
        ])->actions([
            A\ActionGroup::make([
            A\Action::make('edit')->fillForm(fn (Module $record) => $record->only('title', 'position'))->form([F\TextInput::make('title')->required()->maxLength(200), F\TextInput::make('position')->integer()->minValue(1)->required(), Fields::reason()])->action(fn (array $data, Module $record) => Workflow::run(AdminCourseController::class, 'reviseModule', $data, $record->course, $record)),
            A\Action::make('lessons')->url(fn (Module $record) => LessonResource::getUrl('index', ['tableFilters' => ['module_id' => ['value' => $record->id]]])),
            A\Action::make('remove')->color('danger')->form([Fields::reason()])->requiresConfirmation()->action(fn (array $data, Module $record) => Workflow::run(AdminCourseController::class, 'removeModule', $data, $record->course, $record)),
        
            ])->label('Manage')->icon('heroicon-o-ellipsis-horizontal')->button()->color('gray'),
        ])->defaultSort('position')->emptyStateHeading('No chapters yet')->emptyStateDescription('Add a numbered chapter to organize the learning journey.');
    }

    public static function getPages(): array
    {
        return ['index' => ModuleResource\Pages\ListModules::route('/')];
    }
}
