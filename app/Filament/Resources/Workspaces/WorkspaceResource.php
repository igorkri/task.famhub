<?php

namespace App\Filament\Resources\Workspaces;

use App\Filament\Resources\Workspaces\Pages\ManageWorkspaces;
use App\Models\Navigation;
use App\Models\Workspace;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkspaceResource extends Resource
{
    protected static ?string $model = Workspace::class;

    protected static string|BackedEnum|null $navigationIcon = Navigation::NAVIGATION['WORKSPACE']['ICON']; // Heroicon::OutlinedRectangleStack;

    protected static string|null|\UnitEnum $navigationGroup = Navigation::NAVIGATION['WORKSPACE']['GROUP'];

    protected static ?int $navigationSort = Navigation::NAVIGATION['WORKSPACE']['SORT'];

    // назва в меню
    protected static ?string $navigationLabel = Navigation::NAVIGATION['WORKSPACE']['LABEL'];

    // 👇 ось так правильно
    public static function getModelLabel(): string
    {
        return Navigation::NAVIGATION['WORKSPACE']['LABEL'];
    }

    public static function getPluralLabel(): string
    {
        return Navigation::NAVIGATION['WORKSPACE']['LABEL'];
    }

    public static function getNavigationLabel(): string
    {
        return Navigation::NAVIGATION['WORKSPACE']['LABEL'];
    }
    // опис

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //                TextInput::make('gid'),
                TextInput::make('name')
                    ->label('Назва')
                    ->required(),
                RichEditor::make('description')->label('Опис')->nullable(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Опис')
                    ->limit(100)
                    ->html()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWorkspaces::route('/'),
        ];
    }
}
