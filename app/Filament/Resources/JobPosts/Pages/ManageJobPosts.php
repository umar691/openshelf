<?php

namespace App\Filament\Resources\JobPosts\Pages;

use App\Filament\Resources\JobPosts\JobPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJobPosts extends ManageRecords
{
    protected static string $resource = JobPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
