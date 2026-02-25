<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks\Pages;

use App\Filament\Resources\ContractorActOfCompletedWorks\ContractorActOfCompletedWorkResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContractorActOfCompletedWork extends ViewRecord
{
    protected static string $resource = ContractorActOfCompletedWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Завантажити PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('admin.contractor-act-of-completed-works.pdf', ['act' => $this->record]))
                ->openUrlInNewTab()
                ->color('success'),
            Action::make('print')
                ->label('Друк')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('admin.contractor-act-of-completed-works.print', ['act' => $this->record]))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
