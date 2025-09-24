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

    protected static string|BackedEnum|null $navigationIcon = Navigation::GROUPS['MANAGEMENT']['ICON']; //Heroicon::OutlinedRectangleStack;
    protected static string|null|\UnitEnum $navigationGroup = Navigation::GROUPS['MANAGEMENT']['LABEL'];
    protected static ?int $navigationSort = Navigation::GROUPS['MANAGEMENT']['SORT'];

    // назва в меню
    protected static ?string $navigationLabel = Navigation::GROUPS['MANAGEMENT']['LABEL'];

    // 👇 ось так правильно
    public static function getModelLabel(): string
    {
        return Navigation::$navigation['WORKSPACE']['label'];
    }

    public static function getPluralLabel(): string
    {
        return Navigation::$navigation['WORKSPACE']['label'];
    }

    public static function getNavigationLabel(): string
    {
        return Navigation::$navigation['WORKSPACE']['label'];
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
