<?php

namespace App\Filament\Develop\Resources\Develop\DevelopmentPrices\Pages;

use App\Filament\Develop\Resources\Develop\DevelopmentPrices\DevelopmentPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDevelopmentPrices extends ListRecords
{
    protected static string $resource = DevelopmentPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
