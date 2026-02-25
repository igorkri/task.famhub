<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks\Pages;

use App\Filament\Resources\ContractorActOfCompletedWorks\ContractorActOfCompletedWorkResource;
use App\Models\Contractor;
use Filament\Resources\Pages\CreateRecord;

class CreateContractorActOfCompletedWork extends CreateRecord
{
    protected static string $resource = ContractorActOfCompletedWorkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['contractor_id'] = $data['contractor_id']
            ?? Contractor::myCompany()->first()?->id;

        return $data;
    }
}
