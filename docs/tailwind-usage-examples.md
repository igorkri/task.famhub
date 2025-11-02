# Приклади використання Tailwind CSS у Filament

## ✅ Готово до використання!

Після налаштування custom theme ви можете використовувати Tailwind класи безпосередньо у ваших PHP файлах та Blade views.

## 📋 Практичні приклади

### 1. Стилізація Form Section

```php
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

Section::make('Контактна інформація')
    ->description('Введіть контактні дані клієнта')
    ->schema([
        TextInput::make('name')
            ->label('Ім\'я')
            ->required(),
        TextInput::make('email')
            ->label('Email')
            ->email(),
        Textarea::make('notes')
            ->label('Примітки')
            ->rows(3),
    ])
    ->extraAttributes([
        'class' => 'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/50 dark:to-indigo-950/50',
    ])
    ->columns(2)
```

### 2. Кастомна таблиця з градієнтом

```php
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('name')
                ->label('Назва')
                ->extraAttributes([
                    'class' => 'font-bold text-blue-600 dark:text-blue-400',
                ]),
            TextColumn::make('status')
                ->badge()
                ->extraAttributes([
                    'class' => 'rounded-full px-3 py-1',
                ]),
        ])
        ->headerActions([
            // ...
        ])
        ->extraAttributes([
            'class' => 'border-2 border-blue-200 dark:border-blue-800 rounded-lg',
        ]);
}
```

### 3. Action з градієнтом

```php
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;

Action::make('exportData')
    ->label('Експортувати')
    ->icon('heroicon-o-arrow-down-tray')
    ->size(ActionSize::Large)
    ->extraAttributes([
        'class' => 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 transition-all duration-300',
    ])
    ->action(fn () => /* ... */)
```

### 4. Widget з кастомними стилями

```php
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

protected function getStats(): array
{
    return [
        Stat::make('Всього задач', '127')
            ->description('Збільшення на 12%')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/50 dark:to-emerald-950/50 border-l-4 border-green-500',
            ]),
        
        Stat::make('В процесі', '45')
            ->description('Активні задачі')
            ->descriptionIcon('heroicon-m-clock')
            ->color('warning')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/50 dark:to-orange-950/50 border-l-4 border-amber-500',
            ]),
    ];
}
```

### 5. Custom Page з повною стилізацією

```php
namespace App\Filament\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';
    
    public function getViewData(): array
    {
        return [
            'stats' => [
                [
                    'label' => 'Активні проекти',
                    'value' => 12,
                    'icon' => 'heroicon-o-folder',
                    'color' => 'blue',
                ],
                [
                    'label' => 'Завершені',
                    'value' => 34,
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'green',
                ],
            ],
        ];
    }
}
```

**Blade view** (`resources/views/filament/pages/dashboard.blade.php`):

```blade
<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($stats as $stat)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-{{ $stat['color'] }}-500 hover:shadow-2xl transition-shadow duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                            {{ $stat['label'] }}
                        </p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ $stat['value'] }}
                        </p>
                    </div>
                    <div class="p-3 bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30 rounded-full">
                        <x-filament::icon
                            :icon="$stat['icon']"
                            class="w-8 h-8 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400"
                        />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950/50 dark:to-pink-950/50 rounded-2xl p-8 border border-purple-200 dark:border-purple-800">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            Швидкі дії
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="#" class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-lg hover:shadow-md transition-all">
                <span class="text-2xl">📝</span>
                <span class="font-medium">Створити задачу</span>
            </a>
            <a href="#" class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-lg hover:shadow-md transition-all">
                <span class="text-2xl">📊</span>
                <span class="font-medium">Переглянути звіти</span>
            </a>
        </div>
    </div>
</x-filament-panels::page>
```

### 6. Кастомний компонент форми з градієнтами

```php
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

Grid::make(2)
    ->schema([
        Section::make('Пріоритет: Високий')
            ->schema([
                // ... fields
            ])
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-950/50 dark:to-orange-950/50 border-l-4 border-red-500',
            ]),
        
        Section::make('Пріоритет: Середній')
            ->schema([
                // ... fields
            ])
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-950/50 dark:to-amber-950/50 border-l-4 border-yellow-500',
            ]),
    ])
```

## 🎨 Корисні Tailwind класи для Filament

### Градієнти
```php
'class' => 'bg-gradient-to-r from-blue-500 to-purple-600'
'class' => 'bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/50 dark:to-emerald-950/50'
```

### Тіні та ефекти
```php
'class' => 'shadow-lg hover:shadow-2xl transition-shadow duration-300'
'class' => 'rounded-2xl border-2 border-blue-200 dark:border-blue-800'
```

### Анімації
```php
'class' => 'hover:scale-105 transition-transform duration-300'
'class' => 'animate-pulse'
'class' => 'hover:bg-blue-100 dark:hover:bg-blue-900 transition-colors'
```

### Типографіка
```php
'class' => 'text-3xl font-bold text-gray-900 dark:text-white'
'class' => 'text-sm text-gray-600 dark:text-gray-400 font-medium'
```

## ⚠️ Важливі нотатки

1. **Динамічні класи** - якщо використовуєте динамічні класи (наприклад, `border-{{ $color }}-500`), переконайтеся, що вони додані до `safelist` у `tailwind.config.js`

2. **Після змін** - завжди запускайте:
   ```bash
   npm run build  # або npm run dev
   php artisan view:clear && php artisan cache:clear
   ```

3. **Dark mode** - завжди додавайте dark mode варіанти:
   ```php
   'class' => 'bg-blue-50 dark:bg-blue-950'
   ```

## 🚀 Подальші можливості

- Створюйте власні Tailwind компоненти у `theme.css`
- Використовуйте `@layer` для організації стилів
- Додавайте кастомні кольори у Filament panel provider
- Експериментуйте з Tailwind plugins

---

**Статус**: ✅ Повністю налаштовано та готово до використання!
