<?php

namespace App\Filament\Develop\Resources\Develop\DevelopmentPrices\Pages;

use App\Filament\Develop\Resources\Develop\DevelopmentPrices\DevelopmentPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDevelopmentPrice extends EditRecord
{
    protected static string $resource = DevelopmentPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
