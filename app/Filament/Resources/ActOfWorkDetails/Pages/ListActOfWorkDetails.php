<?php

namespace App\Filament\Resources\ActOfWorkDetails\Pages;

use App\Filament\Resources\ActOfWorkDetails\ActOfWorkDetailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActOfWorkDetails extends ListRecords
{
    protected static string $resource = ActOfWorkDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
