<?php

namespace App\Filament\Resources\ActOfWorks\Pages;

use App\Filament\Resources\ActOfWorks\ActOfWorkResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewActOfWork extends ViewRecord
{
    protected static string $resource = ActOfWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
