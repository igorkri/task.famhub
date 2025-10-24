# Настройка цветов в Filament

## Содержание
1. [Базовая настройка цветов](#базовая-настройка-цветов)
2. [Кастомные цветовые схемы](#кастомные-цветовые-схемы)
3. [Темная тема](#темная-тема)
4. [Примеры использования](#примеры-использования)

## Базовая настройка цветов

### 1. Через конфигурационный файл

Создайте или обновите файл `config/filament.php`:

```php
return [
    'colors' => [
        'primary' => Color::hex('#FF0000'), // Красный
        'danger' => Color::rgb(255, 0, 0),
        'info' => Color::hex('#0000FF'),
        'success' => Color::hex('#00FF00'),
        'warning' => Color::hex('#FFA500'),
    ],
];
```

### 2. Через панель администратора

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->colors([
            'primary' => Color::hex('#FF0000'),
            'danger' => Color::rgb(255, 0, 0),
            'info' => Color::hex('#0000FF'),
            'success' => Color::hex('#00FF00'),
            'warning' => Color::hex('#FFA500'),
        ]);
}
```

## Кастомные цветовые схемы

### 1. Создание собственной схемы

```php
use Filament\Support\Colors\Color;

public function panel(Panel $panel): Panel
{
    return $panel
        ->colors([
            'custom' => [
                50 => '#fdf2f8',
                100 => '#fce7f3',
                200 => '#fbcfe8',
                300 => '#f9a8d4',
                400 => '#f472b6',
                500 => '#ec4899',
                600 => '#db2777',
                700 => '#be185d',
                800 => '#9d174d',
                900 => '#831843',
                950 => '#500724',
            ],
        ]);
}
```

### 2. Использование предустановленных цветов

```php
use Filament\Support\Colors\Color;

public function panel(Panel $panel): Panel
{
    return $panel
        ->colors([
            'danger' => Color::Red,
            'gray' => Color::Zinc,
            'info' => Color::Blue,
            'primary' => Color::Amber,
            'success' => Color::Green,
            'warning' => Color::Orange,
        ]);
}
```

## Темная тема

### 1. Включение темной темы

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->darkMode(true);
}
```

### 2. Настройка цветов для темной темы

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->colors([
            'primary' => [
                '50' => '#fff7ed',
                // ...light mode colors
                '950' => '#431407',
            ],
            'primary-dark' => [ // Цвета для темной темы
                '50' => '#422006',
                // ...dark mode colors
                '950' => '#fff7ed',
            ],
        ]);
}
```

## Примеры использования

### 1. Базовая настройка основных цветов

```php
use Filament\Support\Colors\Color;

public function panel(Panel $panel): Panel
{
    return $panel
        ->colors([
            'primary' => Color::Amber,
            'secondary' => Color::Gray,
            'success' => Color::Green,
            'warning' => Color::Yellow,
            'danger' => Color::Red,
        ]);
}
```

### 2. Кастомные цвета для компонентов

```php
use Filament\Support\Colors\Color;

Button::make('create')
    ->color('custom') // Использование кастомного цвета
    ->icon('heroicon-o-plus');

TextInput::make('name')
    ->required()
    ->color('warning'); // Использование предустановленного цвета
```

### 3. Цвета для уведомлений

```php
Notification::make()
    ->success() // или ->danger(), ->warning(), ->info()
    ->title('Saved successfully')
    ->send();
```

## Важные замечания

1. Структура цветов:
   - Каждый цвет должен иметь оттенки от 50 до 950
   - Для темной темы используйте суффикс '-dark'
   - Можно использовать как hex (#FF0000), так и rgb(255, 0, 0)

2. Предустановленные цвета:
   - Blue
   - Gray
   - Green
   - Orange
   - Red
   - Yellow
   - и другие из Tailwind CSS палитры

3. Применение цветов:
   - В формах
   - В кнопках
   - В уведомлениях
   - В иконках
   - В статус-бейджах

4. Оптимизация:
   - Используйте единую цветовую схему
   - Учитывайте контраст для доступности
   - Тестируйте цвета в светлой и темной темах
