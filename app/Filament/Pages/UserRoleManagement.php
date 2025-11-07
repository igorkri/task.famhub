<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRoleManagement extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected string $view = 'filament.pages.user-role-management';

    protected static ?string $navigationLabel = 'Управління ролями';

    protected static ?string $title = 'Управління ролями користувачів';

    protected static string|\UnitEnum|null $navigationGroup = 'Налаштування';

    protected static ?int $navigationSort = 85;

    public array $stats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    protected function loadStats(): void
    {
        $this->stats = [
            'users' => User::count(),
            'roles' => Role::count(),
            'permissions' => Permission::count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->with(['roles']))
            ->columns([
                TextColumn::make('name')
                    ->label('Ім\'я')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('roles.name')
                    ->label('Ролі')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'panel_user' => 'success',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Супер Адмін',
                        'panel_user' => 'Користувач',
                        default => ucfirst($state),
                    }),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('manageRoles')
                    ->label('Ролі')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->form([
                        CheckboxList::make('roles')
                            ->label('Ролі користувача')
                            ->options(Role::all()->pluck('name', 'id'))
                            ->descriptions(
                                Role::all()->mapWithKeys(function ($role) {
                                    return [$role->id => $role->permissions->count().' прав'];
                                })->toArray()
                            )
                            ->columns(2),
                    ])
                    ->fillForm(fn (User $record): array => [
                        'roles' => $record->roles->pluck('id')->toArray(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->syncRoles(Role::whereIn('id', $data['roles'])->pluck('name')->toArray());

                        Notification::make()
                            ->success()
                            ->title('Ролі оновлено')
                            ->body("Ролі користувача {$record->name} успішно оновлено")
                            ->send();
                    }),

                Action::make('makeSuperAdmin')
                    ->label('Зробити супер адміном')
                    ->icon('heroicon-o-star')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Призначити супер адміна?')
                    ->modalDescription(fn (User $record) => "Користувач {$record->name} отримає повний доступ до системи.")
                    ->visible(fn (User $record): bool => ! $record->hasRole('super_admin'))
                    ->action(function (User $record): void {
                        $record->assignRole('super_admin');

                        Notification::make()
                            ->success()
                            ->title('Супер адміна призначено')
                            ->body("{$record->name} тепер має роль супер адміна")
                            ->send();
                    }),

                Action::make('removeSuperAdmin')
                    ->label('Зняти супер адміна')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Зняти роль супер адміна?')
                    ->modalDescription(fn (User $record) => "Користувач {$record->name} втратить повний доступ.")
                    ->visible(fn (User $record): bool => $record->hasRole('super_admin'))
                    ->action(function (User $record): void {
                        $record->removeRole('super_admin');

                        Notification::make()
                            ->success()
                            ->title('Роль знято')
                            ->body("{$record->name} більше не супер адмін")
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Користувачів не знайдено')
            ->emptyStateDescription('Додайте користувачів для управління їх ролями')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignRole')
                ->label('Призначити роль')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->form([
                    Select::make('user_id')
                        ->label('Користувач')
                        ->options(function () {
                            return \App\Models\User::usersList();
                        })
                        ->searchable()
                        ->required(),

                    CheckboxList::make('roles')
                        ->label('Ролі')
                        ->options(Role::all()->pluck('name', 'id'))
                        ->required()
                        ->columns(2),
                ])
                ->action(function (array $data): void {
                    $user = User::find($data['user_id']);
                    $roles = Role::whereIn('id', $data['roles'])->pluck('name')->toArray();
                    $user->syncRoles($roles);

                    Notification::make()
                        ->success()
                        ->title('Ролі призначено')
                        ->body("Користувачу {$user->name} призначено ролі")
                        ->send();
                }),

            Action::make('generatePermissions')
                ->label('Згенерувати права')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Згенерувати права для всіх ресурсів?')
                ->modalDescription('Це створить права для всіх Filament ресурсів згідно з Shield.')
                ->action(function (): void {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('shield:generate', [
                            '--all' => true,
                            '--no-interaction' => true,
                        ]);

                        $this->loadStats();

                        Notification::make()
                            ->success()
                            ->title('Права згенеровано')
                            ->body('Права для всіх ресурсів успішно створено')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка генерації')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('refresh')
                ->label('Оновити')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadStats();

                    Notification::make()
                        ->success()
                        ->title('Дані оновлено')
                        ->send();
                }),
        ];
    }
}
