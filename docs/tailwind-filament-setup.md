# Підключення Tailwind CSS до Filament

## ✅ Налаштування завершено

Tailwind CSS 4.0 успішно підключено до Filament у вашому проекті з використанням **custom theme**.

## 📦 Встановлені пакети

```json
{
  "@tailwindcss/vite": "^4.0.0",
  "tailwindcss": "^4.0.0",
  "vite": "^7.0.4"
}
```

## 🔧 Конфігурація

### 1. Vite Config (`vite.config.js`)

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament.css',
                'resources/css/filament/admin/theme.css', // Custom theme
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

### 2. Custom Theme (`resources/css/filament/admin/theme.css`)

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

/* Директиви @source вказують Tailwind, де шукати класи */
@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
@source '../../../../resources/views/**/*.blade.php';
@source '../../../../app/Livewire/**/*.php';
```

**Важливо**: Директиви `@source` дозволяють використовувати Tailwind класи у ваших:
- PHP файлах (Filament Resources, Pages, Widgets)
- Blade views
- Livewire компонентах

### 3. Tailwind Config (`tailwind.config.js`)

```javascript
import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    // ... інші налаштування
};
```

### 4. Filament Panel Provider (`app/Providers/Filament/AdminPanelProvider.php`)

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->viteTheme('resources/css/filament/admin/theme.css')
        // ...
}
```

## 🎨 Використання Tailwind CSS у Filament

### ✅ Тепер можна використовувати Tailwind класи безпосередньо!

Завдяки custom theme з директивами `@source`, ви можете використовувати Tailwind класи у:

#### 1. PHP файлах (Filament Resources)

```php
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Інформація')
                ->schema([
                    TextInput::make('name')
                        ->label('Назва'),
                ])
                ->extraAttributes([
                    'class' => 'bg-blue-50 dark:bg-blue-950 rounded-lg p-4',
                ]),
        ]);
}
```

#### 2. Використання у Table Columns

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('status')
    ->badge()
    ->extraAttributes([
        'class' => 'font-bold text-lg',
    ])
```

#### 3. Використання у Actions

```php
use Filament\Actions\Action;

Action::make('submit')
    ->label('Відправити')
    ->extraAttributes([
        'class' => 'bg-gradient-to-r from-blue-500 to-purple-600',
    ])
```

#### 4. Blade Views

```blade
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        Заголовок
    </h2>
    <p class="mt-2 text-gray-600 dark:text-gray-400">
        Опис
    </p>
</div>
```

### Застосування класів Tailwind до компонентів

```php
use Filament\Forms\Components\Section;

Section::make()
    ->schema([
        // ...
    ])
    ->extraAttributes([
        'class' => 'bg-gradient-to-r from-blue-50 to-indigo-50',
    ])
```

### Перевизначення стилів Filament

У файлі `resources/css/app.css`:

```css
/* Зміна border-radius кнопок */
.fi-btn {
    @apply rounded-sm;
}

/* Зміна кольору сайдбару */
.fi-sidebar {
    @apply bg-gray-50 dark:bg-gray-950;
}
```

### Використання CSS hook класів

Filament використовує класи з префіксом `fi-` для стилізації:

```css
/* Приклад: стилізація заголовка таблиці */
.fi-ta-header {
    @apply bg-blue-50 dark:bg-blue-950;
}
```

## 🛠️ Команди для роботи

### Збірка для продакшну
```bash
npm run build
```

### Режим розробки (watch mode)
```bash
npm run dev
```

### Очистка кешу
```bash
php artisan view:clear && php artisan cache:clear && php artisan filament:cache-components
```

### Створення нової теми
```bash
php artisan make:filament-theme admin --pm=npm
```

## 📁 Додавання нових директорій для сканування

Якщо ви створили нові директорії з Blade views або PHP файлами, додайте їх до `theme.css`:

```css
/* resources/css/filament/admin/theme.css */
@source '../../../../app/YourCustomFolder/**/*.php';
@source '../../../../resources/views/your-folder/**/*.blade.php';
```

**Після додавання нових директорій:**
1. Запустіть `npm run build`
2. Очистіть кеш: `php artisan view:clear && php artisan cache:clear`

## 📝 Safelist класів

У `tailwind.config.js` можна додати класи до `safelist`, щоб вони завжди включалися в фінальний CSS:

```javascript
export default {
    // ...
    safelist: [
        'bg-blue-50',
        'text-red-600',
        // інші класи
    ],
};
```

## 🎯 Рекомендації

1. **Використовуйте CSS hook класи** замість публікації Blade views
2. **Блокуйте версії Filament** у `composer.json`, якщо публікуєте views
3. **Використовуйте `@apply`** для застосування Tailwind класів
4. **Використовуйте `!important` обережно** - тільки коли необхідно

## 🔗 Корисні посилання

- [Filament Styling Documentation](https://filamentphp.com/docs/4.x/styling)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Filament CSS Hooks](https://filamentphp.com/docs/4.x/styling/css-hooks)

## ✨ Приклади кастомних стилів

### Кастомний компонент таймера

```css
.timer-container {
    @apply bg-gradient-to-r from-blue-50 to-indigo-50 
           dark:from-blue-900/20 dark:to-indigo-900/20 
           rounded-xl p-6 border border-blue-200 
           dark:border-blue-800;
}

.timer-main-time {
    @apply text-3xl font-bold text-blue-600 dark:text-blue-400;
}
```

### Кастомні кольори для Rich Editor

```php
use Filament\Forms\Components\RichEditor;

RichEditor::make('content')
    ->textColors([
        '#ef4444' => 'Червоний',
        '#10b981' => 'Зелений',
        '#0ea5e9' => 'Синій',
    ])
```

## 🚀 Наступні кроки

1. Запустіть `npm run dev` для режиму розробки
2. Відкрийте Filament панель у браузері
3. Перевірте, що всі стилі застосовуються коректно
4. Додайте ваші кастомні стилі у `resources/css/app.css`

---

**Статус**: ✅ Готово до використання
**Версія Tailwind**: 4.0.0
**Версія Filament**: 4.x
