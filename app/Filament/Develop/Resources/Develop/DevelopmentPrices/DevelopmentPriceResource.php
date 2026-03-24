<?php

namespace App\Filament\Develop\Resources\Develop\DevelopmentPrices;

use App\Filament\Develop\Resources\Develop\DevelopmentPrices\Pages\CreateDevelopmentPrice;
use App\Filament\Develop\Resources\Develop\DevelopmentPrices\Pages\EditDevelopmentPrice;
use App\Filament\Develop\Resources\Develop\DevelopmentPrices\Pages\ListDevelopmentPrices;
use App\Filament\Develop\Resources\Develop\DevelopmentPrices\Schemas\DevelopmentPriceForm;
use App\Filament\Develop\Resources\Develop\DevelopmentPrices\Tables\DevelopmentPricesTable;
use App\Models\Develop\DevelopmentPrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DevelopmentPriceResource extends Resource
{
    protected static ?string $model = DevelopmentPrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DevelopmentPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevelopmentPricesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevelopmentPrices::route('/'),
            'create' => CreateDevelopmentPrice::route('/create'),
            'edit' => EditDevelopmentPrice::route('/{record}/edit'),
        ];
    }
}
