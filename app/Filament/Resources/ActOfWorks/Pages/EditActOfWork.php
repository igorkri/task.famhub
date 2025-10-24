<?php

namespace App\Filament\Resources\ActOfWorks\Pages;

use App\Filament\Resources\ActOfWorks\ActOfWorkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActOfWork extends EditRecord
{
    protected static string $resource = ActOfWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
