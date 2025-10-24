<?php

namespace App\Filament\Resources\ActOfWorks\Pages;

use App\Filament\Resources\ActOfWorks\ActOfWorkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActOfWorks extends ListRecords
{
    protected static string $resource = ActOfWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
