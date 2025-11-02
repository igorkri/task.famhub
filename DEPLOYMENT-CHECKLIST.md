# Чеклист для деплою на сервер

## 1. Git - Завантажити зміни на сервер
```bash
# На локальній машині
git add .
git commit -m "UI improvements: compact custom fields, better statistics display, Tailwind integration"
git push origin main

# На сервері
cd /path/to/project
git pull origin main
```

## 2. Composer - Оновити залежності (якщо потрібно)
```bash
composer install --no-dev --optimize-autoloader
```

## 3. NPM - Зібрати frontend assets (ОБОВ'ЯЗКОВО!)
```bash
# Встановити залежності (якщо ще не встановлені)
npm install

# Зібрати для продакшну
npm run build
```

## 4. Laravel - Очистити кеш
```bash
# Очистити всі кеші
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Оптимізувати для продакшну
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5. Перевірити права доступу
```bash
# Переконатися що storage та bootstrap/cache мають правильні права
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 6. Перезапустити процеси (якщо використовуєте)
```bash
# Якщо використовуєте queue workers
php artisan queue:restart

# Якщо використовуєте Octane
php artisan octane:reload

# Якщо використовуєте PHP-FPM
sudo systemctl restart php8.3-fpm

# Якщо використовуєте Nginx
sudo systemctl reload nginx
```

## Мінімальний набір команд для швидкого деплою:
```bash
git pull origin main
npm run build
php artisan cache:clear
php artisan config:cache
php artisan view:clear
```

## Перевірка після деплою:
1. ✅ Відкрити сайт у браузері
2. ✅ Відкрити форму редагування таска
3. ✅ Перевірити що Tailwind стилі застосовані (градієнти, кольори)
4. ✅ Перевірити що кастомні поля відображаються в 2 колонки
5. ✅ Перевірити що статистика виглядає красиво
6. ✅ Очистити кеш браузера (Ctrl+Shift+R) якщо стилі не застосувалися

## Важливо!
- **npm run build** - це найважливіша команда! Без неї Tailwind стилі не будуть скомпільовані
- Перевірте що файл `public/build/manifest.json` оновився після білду
- Переконайтеся що `public/build/assets/theme-*.css` існує та має великий розмір (~580KB)
