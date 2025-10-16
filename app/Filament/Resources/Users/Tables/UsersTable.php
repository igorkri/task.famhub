<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ім\'я')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email скопійовано'),
                
                IconColumn::make('email_verified_at')
                    ->label('Email підтверджено')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->sortable(),
                
                TextColumn::make('roles.name')
                    ->label('Ролі')
                    ->badge()
                    ->searchable(),
                
                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('asana_gid')
                    ->label('Asana GID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Роль')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple(),
                
                SelectFilter::make('email_verified')
                    ->label('Email підтверджено')
                    ->options([
                        'verified' => 'Так',
                        'not_verified' => 'Ні',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'verified') {
                            return $query->whereNotNull('email_verified_at');
                        }
                        if ($state['value'] === 'not_verified') {
                            return $query->whereNull('email_verified_at');
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
