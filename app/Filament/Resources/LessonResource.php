<?php

namespace App\Filament\Resources;

use App\Filament\Support\AdminWorkflows as Workflow;
use App\Filament\Support\Fields;
use App\Filament\Support\StudioResource;
use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\MediaController;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Filament\Forms\Components as F;
use Filament\Tables\Actions as A;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonResource extends StudioResource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationGroup = 'Curriculum';

    protected static ?int $navigationSort = 3;

    /** @return array<\Filament\Forms\Components\Component> */
    public static function lessonFields(): array
    {
        return [F\TextInput::make('title')->label('Lesson title')->required()->maxLength(200), F\Textarea::make('summary')->maxLength(500), F\Select::make('type')->disabled(fn (?Lesson $record) => $record?->module->course->enrollments()->exists() ?? false)->dehydrated()->label('Lesson type')->options(['text' => 'Text', 'video' => 'Video', 'live' => 'Live'])->native(false)->default('text')->required(), F\TextInput::make('position')->disabled(fn (?Lesson $record) => $record?->module->course->enrollments()->exists() ?? false)->dehydrated()->label('Lesson number')->integer()->minValue(1)->default(1)->required(), F\Toggle::make('required')->disabled(fn (?Lesson $record) => $record?->module->course->enrollments()->exists() ?? false)->dehydrated()->label('Required for completion')->default(true), F\Toggle::make('published')->disabled(fn (?Lesson $record) => $record?->module->course->enrollments()->exists() ?? false)->dehydrated()->label('Published')->default(false)->helperText('Video needs a ready recording; live needs a scheduled session.'), F\Textarea::make('body')->label('Text lesson content (Markdown)')->rows(6)->maxLength(100000)];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([C\TextColumn::make('module.course.title')->label('Course')->searchable()->wrap(), C\TextColumn::make('module.title')->label('Chapter'), C\TextColumn::make('title')->searchable()->wrap(), C\TextColumn::make('position')->sortable(), C\TextColumn::make('type')->badge(), C\IconColumn::make('required')->boolean(), C\IconColumn::make('published')->boolean(), C\TextColumn::make('video_status')->label('Recording')->badge()])->filters([
            SelectFilter::make('course')->native(false)->options(fn () => Course::pluck('title', 'id'))->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $q, $id) => $q->whereHas('module', fn (Builder $m) => $m->where('course_id', $id)))),
            SelectFilter::make('module_id')->native(false)->label('Chapter')->options(fn () => Module::pluck('title', 'id')),
            SelectFilter::make('type')->native(false)->options(['text' => 'Text', 'video' => 'Video', 'live' => 'Live']),
        ])->headerActions([
            A\Action::make('create')->label('Add lesson')->form([F\Select::make('module_id')->label('Chapter')->options(fn () => Module::with('course')->get()->mapWithKeys(fn ($m) => [$m->id => $m->course->title.' / '.$m->title]))->native(false)->required(), ...static::lessonFields()])->action(function (array $data) {
                $module = Module::findOrFail((int) $data['module_id']);

                return Workflow::run(AdminCourseController::class, 'lesson', $data, $module->course, $module);
            }),
        ])->actions([
            A\ActionGroup::make([
            A\Action::make('edit')->fillForm(fn (Lesson $record) => $record->only('title', 'summary', 'body', 'type', 'position', 'required', 'published'))->form([...static::lessonFields(), Fields::reason()])->action(fn (array $data, Lesson $record) => Workflow::run(AdminCourseController::class, 'reviseLesson', $data, $record->module->course, $record)),
            A\Action::make('recording')->label('Attach recording')->visible(fn (Lesson $record) => in_array($record->type, ['video', 'live']))->form([F\TextInput::make('video_uid')->label('Cloudflare Stream video ID')->required()->regex('/^[a-f0-9]{32}$/')->helperText('The server checks ownership, private playback, allowed origins and readiness.'), Fields::reason()])->action(fn (array $data, Lesson $record) => Workflow::run(MediaController::class, 'attach', $data, $record)),
            A\Action::make('schedule')->visible(fn (Lesson $record) => $record->type === 'live')->fillForm(fn (Lesson $record) => $record->session?->only('provider', 'join_url', 'starts_at', 'ends_at', 'timezone', 'status', 'message') ?? ['timezone' => config('platform.timezone'), 'status' => 'scheduled'])->form([
                F\TextInput::make('provider')->required()->maxLength(80), F\TextInput::make('join_url')->label('Protected meeting URL')->url()->required()->maxLength(2000)->helperText('Only configured meeting hosts are accepted. Stored encrypted.'),
                F\DateTimePicker::make('starts_at')->label('Starts at (UTC)')->native(false)->seconds(false)->required(), F\DateTimePicker::make('ends_at')->label('Ends at (UTC)')->native(false)->seconds(false)->required(), F\TextInput::make('timezone')->label('Display timezone')->required()->default('Asia/Dubai'), F\Select::make('status')->options(['scheduled' => 'Scheduled', 'cancelled' => 'Cancelled', 'completed' => 'Completed'])->native(false)->required()->default('scheduled'), F\Textarea::make('message')->maxLength(1000), Fields::reason(),
            ])->action(fn (array $data, Lesson $record) => Workflow::run(MediaController::class, 'schedule', $data, $record)),
            A\Action::make('preview')->label('Preview recording')->visible(fn (Lesson $record) => $record->video_status === 'ready')->modalContent(fn (Lesson $record) => view('filament.recording', ['lesson' => $record]))->modalSubmitAction(false)->modalCancelActionLabel('Close'),
            A\Action::make('roster')->label('Attendance & progress')->url(fn (Lesson $record) => EnrollmentResource::getUrl('index', ['tableFilters' => ['course_id' => ['value' => $record->module->course_id]]])),
            A\Action::make('remove')->color('danger')->form([Fields::reason()])->requiresConfirmation()->action(fn (array $data, Lesson $record) => Workflow::run(AdminCourseController::class, 'removeLesson', $data, $record->module->course, $record)),
        
            ])->label('Manage')->icon('heroicon-o-ellipsis-horizontal')->button()->color('gray'),
        ])->defaultSort('position')->emptyStateHeading('Build your learning path')->emptyStateDescription('Add text, video or live lessons to a chapter.');
    }

    public static function getPages(): array
    {
        return ['index' => LessonResource\Pages\ListLessons::route('/')];
    }
}
