<?php

namespace App\Filament\Resources\Workspaces\Pages;

use App\Filament\Resources\Workspaces\WorkspaceResource;
use App\Models\Navigation;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWorkspaces extends ManageRecords
{
    protected static string $resource = WorkspaceResource::class;

    public function getTitle(): string
    {
        return Navigation::$navigation['WORKSPACE']['label'];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
