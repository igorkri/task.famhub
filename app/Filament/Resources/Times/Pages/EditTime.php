<?php

namespace App\Filament\Resources\Times\Pages;

use App\Filament\Resources\Times\TimeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTime extends EditRecord
{
    protected static string $resource = TimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
