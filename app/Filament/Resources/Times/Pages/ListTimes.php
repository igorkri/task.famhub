<?php

namespace App\Filament\Resources\Times\Pages;

use App\Filament\Resources\Times\TimeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTimes extends ListRecords
{
    protected static string $resource = TimeResource::class;

    public function getTitle(): string
    {
        return TimeResource::getNavigationLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
