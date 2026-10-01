<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Support\AdminWorkflows as Workflow;
use App\Filament\Support\Fields;
use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\CourseImageController;
use Filament\Actions\Action;
use Filament\Forms\Components as F;
use Illuminate\Database\Eloquent\Model;

class EditCourse extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = \App\Filament\Resources\CourseResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof \App\Models\Course);

        return Workflow::saveCourse($data, $record);
    }

    public function getRecord(): \App\Models\Course
    {
        $record = parent::getRecord();
        assert($record instanceof \App\Models\Course);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('curriculum')->color('gray')->label('Chapters')->url(fn () => \App\Filament\Resources\ModuleResource::getUrl('index', ['tableFilters' => ['course_id' => ['value' => $this->getRecord()->id]]])),
            Action::make('lessons')->color('gray')->url(fn () => \App\Filament\Resources\LessonResource::getUrl('index', ['tableFilters' => ['course' => ['value' => $this->getRecord()->id]]])),
            Action::make('preview')->color('gray')->url(fn () => '/admin/courses/'.$this->getRecord()->id.'/preview')->openUrlInNewTab(),
            Action::make('publish')->requiresConfirmation()->action(function () { Workflow::run(AdminCourseController::class, 'publish', [], $this->getRecord()); $this->getRecord()->refresh(); }),
            Action::make('availability')->color('gray')->fillForm(fn () => ['sales_visible' => $this->getRecord()->sales_visible, 'takedown_reason' => $this->getRecord()->takedown_reason])->form([F\Toggle::make('sales_visible')->label('Open for sales'), F\Textarea::make('takedown_reason')->label('Emergency content block')->helperText('Leave empty for normal student access. This message is shown to students.'), Fields::reason()])->action(fn (array $data) => Workflow::run(AdminCourseController::class, 'availability', $data, $this->getRecord())),
            Action::make('image')->color('gray')->label('Course image')->form([F\Select::make('slot')->options(['cover' => 'Cover', 'instructor_photo' => 'Instructor photo'])->required()->native(false), F\FileUpload::make('image')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->storeFiles(false)->required()])->action(function (array $data) {
                $request = Workflow::request(['slot' => $data['slot']]);
                $request->files->set('image', $data['image']);
                Workflow::withHttpRedirector(fn () => app(CourseImageController::class)->store($request, $this->getRecord()));
                \Filament\Notifications\Notification::make()->title('Image saved')->success()->send();
            }),
        ];
    }
}
