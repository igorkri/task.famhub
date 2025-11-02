# 🎨 Tailwind CSS у Filament - Швидкий старт

## ✅ Все налаштовано!

Ви можете використовувати **будь-які Tailwind CSS класи** у ваших:
- ✅ PHP файлах (Resources, Pages, Widgets)
- ✅ Blade views
- ✅ Livewire компонентах

## 🚀 Швидкий приклад

```php
use Filament\Forms\Components\Section;

Section::make('Заголовок')
    ->schema([/* ... */])
    ->extraAttributes([
        'class' => 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6',
    ])
```

## 📚 Документація

- **Повна інструкція**: `docs/tailwind-filament-setup.md`
- **Приклади використання**: `docs/tailwind-usage-examples.md`

## 🛠️ Основні команди

```bash
# Режим розробки
npm run dev

# Збірка для продакшну
npm run build

# Очистка кешу
php artisan view:clear && php artisan cache:clear
```

## 📁 Ключові файли

- `resources/css/filament/admin/theme.css` - custom theme з @source директивами
- `vite.config.js` - конфігурація Vite
- `app/Providers/Filament/AdminPanelProvider.php` - реєстрація theme

## 🎯 Додавання нових директорій

Якщо створюєте нові папки, додайте їх до `theme.css`:

```css
@source '../../../../app/YourFolder/**/*.php';
```

Потім:
```bash
npm run build
php artisan view:clear && php artisan cache:clear
```

---

**Все готово! Використовуйте Tailwind класи де завгодно! 🎉**
