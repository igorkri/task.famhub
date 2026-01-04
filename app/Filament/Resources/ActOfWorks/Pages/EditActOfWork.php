<?php

namespace App\Filament\Resources\ActOfWorks\Pages;

use App\Filament\Resources\ActOfWorks\Actions\GenerateExcelAction;
use App\Filament\Resources\ActOfWorks\Actions\SendToTelegramAction;
use App\Filament\Resources\ActOfWorks\ActOfWorkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActOfWork extends EditRecord
{
    protected static string $resource = ActOfWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateExcelAction::make(),
            SendToTelegramAction::make(),
            DeleteAction::make(),
        ];
    }
}
