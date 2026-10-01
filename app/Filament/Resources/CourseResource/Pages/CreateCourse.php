<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Support\AdminWorkflows;
use Illuminate\Database\Eloquent\Model;

class CreateCourse extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = \App\Filament\Resources\CourseResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return AdminWorkflows::saveCourse($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
