<?php

namespace App\Filament\Resources\CourseResource\Pages;

class ListCourses extends \Filament\Resources\Pages\ListRecords
{
    protected static string $resource = \App\Filament\Resources\CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
