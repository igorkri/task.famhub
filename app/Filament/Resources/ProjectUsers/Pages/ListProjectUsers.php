<?php

namespace App\Filament\Resources\ProjectUsers\Pages;

use App\Filament\Resources\ProjectUsers\ProjectUserResource;
use App\Models\Navigation;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectUsers extends ListRecords
{
    protected static string $resource = ProjectUserResource::class;

    public function getTitle(): string
    {
        return Navigation::NAVIGATION['PROJECT_USER']['LABEL'];
    }
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
