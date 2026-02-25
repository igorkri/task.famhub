<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks\Pages;

use App\Filament\Resources\ContractorActOfCompletedWorks\ContractorActOfCompletedWorkResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContractorActOfCompletedWork extends EditRecord
{
    protected static string $resource = ContractorActOfCompletedWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Друк')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('admin.contractor-act-of-completed-works.print', ['act' => $this->record]))
                ->openUrlInNewTab(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
