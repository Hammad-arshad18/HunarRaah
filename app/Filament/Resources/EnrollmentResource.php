<?php

namespace App\Filament\Resources;

use App\Filament\Support\AdminWorkflows as Workflow;
use App\Filament\Support\Fields;
use App\Filament\Support\StudioResource;
use App\Http\Controllers\AdminOperationsController;
use App\Http\Controllers\MediaController;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Filament\Forms\Components as F;
use Filament\Tables\Actions as A;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentResource extends StudioResource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Student records';

    public static function table(Table $table): Table
    {
        return $table->columns([C\TextColumn::make('user.name')->label('Student')->searchable(), C\TextColumn::make('course.title')->searchable()->wrap(), C\TextColumn::make('status')->badge(), C\TextColumn::make('source')->badge(), C\TextColumn::make('completed_at')->dateTime()->placeholder('In progress'), C\TextColumn::make('restriction_reason')->wrap()->toggleable(isToggledHiddenByDefault: true)])->filters([SelectFilter::make('course_id')->native(false)->label('Course')->options(fn () => Course::pluck('title', 'id')), SelectFilter::make('status')->native(false)->options(['active' => 'Active', 'suspended' => 'Suspended', 'revoked' => 'Revoked'])])->headerActions([
            A\Action::make('grant')->label('Complimentary enrollment')->modalDescription('A complimentary grant creates access with an audit reason and no payment record.')->form([F\Select::make('course_id')->label('Course')->options(fn () => Course::pluck('title', 'id'))->native(false)->required(), F\Select::make('user_id')->label('Student')->options(fn () => User::where('role', 'student')->whereNotNull('email_verified_at')->whereNull('suspended_at')->get()->mapWithKeys(fn ($u) => [$u->id => $u->name.' · '.$u->email]))->native(false)->required(), Fields::reason()])->action(fn (array $data) => Workflow::run(AdminOperationsController::class, 'grant', $data, Course::findOrFail((int) $data['course_id']))),
        ])->actions([
            A\ActionGroup::make([
            A\Action::make('progress')->label('View progress')->modalContent(fn (Enrollment $record) => view('filament.progress', ['enrollment' => $record->load('user', 'progress', 'course.modules.lessons')]))->modalSubmitAction(false)->modalCancelActionLabel('Close'),
            A\Action::make('restriction')->label('Change access')->fillForm(fn (Enrollment $record) => ['status' => $record->status])->form([F\Select::make('status')->options(['active' => 'Active', 'suspended' => 'Suspended', 'revoked' => 'Revoked'])->required()->native(false), Fields::reason()])->action(fn (array $data, Enrollment $record) => Workflow::run(AdminOperationsController::class, 'restrict', $data, $record)),
            A\Action::make('certificate')->label('Issue certificate')->visible(fn (Enrollment $record) => $record->completed_at && $record->course->certificate_enabled)->form([Fields::reason()])->action(function (array $data, Enrollment $record) {
                Workflow::authorize();
                try {
                    $certificate = app(\App\Actions\IssueCertificate::class)->execute($record->user, $record);
                } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
                    \Filament\Notifications\Notification::make()->title('Certificate is not eligible')->body('Check active, verified access and every required lesson.')->danger()->send();
                    throw \Illuminate\Validation\ValidationException::withMessages(['reason' => 'Certificate eligibility checks failed.']);
                }
                \Illuminate\Support\Facades\DB::table('audit_logs')->insert(['actor_id' => auth()->id(), 'action' => 'certificate.admin_issued', 'subject_type' => 'certificate', 'subject_id' => $certificate->id, 'reason' => $data['reason'], 'created_at' => now()]);
                \Filament\Notifications\Notification::make()->title('Certificate issued; PDF generation queued')->success()->send();
            }),
            A\Action::make('completion')->label('Attendance & progress')->modalDescription('Attendance is recorded by an administrator. Corrections need a reason; issued certificates require separate explicit revocation.')->form(fn (Enrollment $record) => [
                F\Select::make('lesson_id')->label('Lesson')->options($record->course->lessons()->where('published', true)->pluck('lessons.title', 'lessons.id'))->required()->native(false), F\Select::make('source')->label('Operation')->options(['attendance' => 'Record live attendance', 'complete' => 'Mark completed (correction)', 'clear' => 'Clear completion (correction)'])->required()->native(false), Fields::reason(),
            ])->action(function (array $data, Enrollment $record) {
                $lesson = Lesson::findOrFail((int) $data['lesson_id']);

                return Workflow::run(MediaController::class, $data['source'] === 'attendance' ? 'attendance' : 'correction', [...$data, 'completed' => $data['source'] !== 'clear'], $lesson, $record);
            }),
        
            ])->label('Manage')->icon('heroicon-o-ellipsis-horizontal')->button()->color('gray'),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => EnrollmentResource\Pages\ListEnrollments::route('/')];
    }
}
