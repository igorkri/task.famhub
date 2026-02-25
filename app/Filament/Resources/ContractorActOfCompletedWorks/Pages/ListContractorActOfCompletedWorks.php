<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks\Pages;

use App\Filament\Resources\ContractorActOfCompletedWorks\ContractorActOfCompletedWorkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContractorActOfCompletedWorks extends ListRecords
{
    protected static string $resource = ContractorActOfCompletedWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
