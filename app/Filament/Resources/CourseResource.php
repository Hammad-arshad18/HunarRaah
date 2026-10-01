<?php

namespace App\Filament\Resources;

use App\Filament\Support\AdminWorkflows as Workflow;
use App\Filament\Support\StudioResource;
use App\Http\Controllers\AdminCourseController;
use App\Models\Course;
use Filament\Forms\Components as F;
use Filament\Forms\Form;
use Filament\Tables\Actions as A;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CourseResource extends StudioResource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Curriculum';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        $locked = fn (?Course $record) => $record?->enrollments()->exists() ?? false;

        return $form->schema([
            F\Section::make('The course')->description('Explain the outcome, then build the learning journey.')->columns(2)->schema([
                F\TextInput::make('title')->label('Course title')->required()->maxLength(200),
                F\TextInput::make('slug')->label('Public URL slug')->required()->maxLength(200)->regex('/^[a-zA-Z0-9_-]+$/'),
                F\Textarea::make('summary')->label('Short summary')->required()->maxLength(500)->columnSpanFull(),
                F\Textarea::make('description')->label('Full description (Markdown)')->required()->maxLength(30000)->rows(5)->columnSpanFull(),
                F\TagsInput::make('outcomes')->label('Learning outcomes')->helperText('Enter each outcome and press Enter.')->required()->columnSpanFull(),
                F\Textarea::make('prerequisites')->maxLength(3000), F\Textarea::make('target_audience')->maxLength(3000),
                F\TextInput::make('level')->required()->default('Beginner')->maxLength(80),
                F\TextInput::make('duration_minutes')->label('Estimated minutes')->integer()->minValue(0)->default(60)->required(),
                F\Select::make('format')->label('Course format')->options(['recorded' => 'Recorded', 'live' => 'Live', 'hybrid' => 'Hybrid'])->default('recorded')->required()->native(false),
                F\DateTimePicker::make('enrollment_deadline')->label('Enrollment deadline (UTC)')->native(false)->seconds(false),
            ]),
            F\Section::make('Instructor and pricing')->columns(2)->schema([
                F\TextInput::make('instructor_name')->label('Instructor display name')->required()->maxLength(200),
                F\Textarea::make('instructor_bio')->label('Instructor biography')->required()->maxLength(5000),
                F\TextInput::make('price_minor')->label('Price in minor units')->helperText('AED 499.00 = 49900. Use 0 for a free course.')->integer()->minValue(0)->maxValue(100000000)->default(0)->required(),
                F\Select::make('currency')->options([config('platform.currency') => config('platform.currency')])->default(config('platform.currency'))->required()->native(false),
            ]),
            F\Section::make('Access and completion')->description('Completion rules are locked after the first enrollment.')->schema([
                F\Textarea::make('access_policy')->required()->maxLength(5000)->default('Ongoing access while enrollment remains active.'),
                F\Textarea::make('completion_policy')->required()->maxLength(5000)->default('Complete every required lesson.')->disabled($locked)->dehydrated(),
                F\Toggle::make('recording_alternative')->label('Allow a live recording instead of attendance')->default(false)->disabled($locked)->dehydrated(),
                F\Toggle::make('certificate_enabled')->label('Offer a certificate of completion')->default(true),
                F\Checkbox::make('accessible_content_confirmed')->label('I confirm accessible learning content, captions or transcripts are available')->required(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            C\TextColumn::make('title')->searchable()->sortable()->description(fn (Course $record) => $record->summary)->wrap(),
            C\TextColumn::make('format')->badge(), C\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                'published' => 'success','archived' => 'gray',default => 'warning'
            }),
            C\TextColumn::make('price_minor')->label('Price')->formatStateUsing(fn ($state, Course $record) => $record->currency.' '.number_format($state / 100, 2)),
            C\IconColumn::make('sales_visible')->label('On sale')->boolean(), C\TextColumn::make('enrollments_count')->counts('enrollments')->label('Enrolled'),
        ])->filters([SelectFilter::make('status')->native(false)->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']), SelectFilter::make('format')->native(false)->options(['recorded' => 'Recorded', 'live' => 'Live', 'hybrid' => 'Hybrid'])])->actions([
            A\EditAction::make(),
            A\ActionGroup::make([
            A\Action::make('preview')->url(fn (Course $record) => '/admin/courses/'.$record->id.'/preview')->openUrlInNewTab()->icon('heroicon-o-eye'),
            A\Action::make('publish')->requiresConfirmation()->modalDescription('Checks required lessons, accessibility, instructor and access policies.')->visible(fn (Course $record) => $record->status !== 'published')->action(fn (Course $record) => Workflow::run(AdminCourseController::class, 'publish', [], $record)),
            A\Action::make('archive')->requiresConfirmation()->modalDescription('Stop new sales while preserving existing student access.')->visible(fn (Course $record) => $record->status !== 'archived')->action(fn (Course $record) => Workflow::run(AdminCourseController::class, 'archive', [], $record)),
            A\Action::make('duplicate')->label('New revision')->action(function (Course $record, $livewire) {
                $response = Workflow::run(AdminCourseController::class, 'duplicate', [], $record);
                $livewire->redirect($response->headers->get('Location'));
            }),
        
            ])->label('Manage')->icon('heroicon-o-ellipsis-horizontal')->button()->color('gray'),
        ])->defaultSort('id', 'desc')->emptyStateHeading('Start your first course')->emptyStateDescription('Create a draft, add the curriculum and publish when it is ready.');
    }

    public static function getPages(): array
    {
        return ['index' => CourseResource\Pages\ListCourses::route('/'), 'create' => CourseResource\Pages\CreateCourse::route('/create'), 'edit' => CourseResource\Pages\EditCourse::route('/{record}/edit')];
    }
}
