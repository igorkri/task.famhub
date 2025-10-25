<?php

namespace App\Filament\Resources\ActOfWorkDetails\Pages;

use App\Filament\Resources\ActOfWorkDetails\ActOfWorkDetailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActOfWorkDetail extends EditRecord
{
    protected static string $resource = ActOfWorkDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
