# Налаштування RichEditor для роботи з зображеннями

## Проблема
Зображення не відображаються в RichEditor при редагуванні задач.

## Рішення

### Автоматичне налаштування
Запустіть скрипт:
```bash
./setup-storage.sh
```

### Ручне налаштування

#### 1. Створити символічне посилання
```bash
php artisan storage:link
```
Це створить посилання: `public/storage -> storage/app/public`

#### 2. Переконатися що директорія існує
```bash
mkdir -p storage/app/public/task-descriptions
```

#### 3. Встановити права доступу
```bash
# Якщо файли належать www-data (Apache/Nginx)
sudo chmod -R 755 storage/app/public
sudo chown -R www-data:www-data storage/app/public

# АБО якщо локальний сервер
chmod -R 755 storage/app/public
```

#### 4. Очистити кеш
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

#### 5. Перевірити APP_URL в .env
```bash
# У файлі .env має бути правильний URL
APP_URL=http://task.famhub.local
```

#### 6. Перевірити доступність файлів
```bash
# Перевірити що файли доступні через браузер
curl -I http://task.famhub.local/storage/task-descriptions/test.png
# Повинно повернути: HTTP/1.1 200 OK
```

## Код в TaskForm.php

В файлі `app/Filament/Resources/Tasks/Schemas/TaskForm.php` має бути:

```php
RichEditor::make('description')
    ->label('Опис')
    ->fileAttachmentsDisk('public')
    ->fileAttachmentsDirectory('task-descriptions')
    ->fileAttachmentsVisibility('public')
    ->columnSpanFull(),
```

### Важливі параметри:
- `fileAttachmentsDisk('public')` - використовуємо диск 'public'
- `fileAttachmentsDirectory('task-descriptions')` - папка для зображень
- `fileAttachmentsVisibility('public')` - **КРИТИЧНО** для Filament 4 (за замовчуванням 'private')

## Структура файлів

```
project/
├── public/
│   └── storage -> ../storage/app/public  (символічне посилання)
├── storage/
│   └── app/
│       └── public/
│           └── task-descriptions/
│               └── [завантажені зображення]
```

## Перевірка роботи

1. Відкрийте задачу в Filament
2. В полі "Опис" натисніть кнопку завантаження зображення
3. Виберіть зображення
4. Після завантаження воно повинно відразу відображатися в редакторі
5. Після збереження зображення має залишатися видимим

## Налаштування на production сервері

### Для Apache
```bash
# 1. Встановити права
sudo chown -R www-data:www-data storage/app/public
sudo chmod -R 755 storage/app/public

# 2. Створити символічне посилання
php artisan storage:link

# 3. Перевірити .htaccess
# У public/.htaccess має бути дозволено доступ до storage
```

### Для Nginx
```nginx
# У конфігурації Nginx:
location /storage {
    alias /path/to/project/storage/app/public;
}
```

### Права доступу
```bash
# Мінімальні права:
# Директорії: 755 (rwxr-xr-x)
# Файли: 644 (rw-r--r--)

find storage/app/public -type d -exec chmod 755 {} \;
find storage/app/public -type f -exec chmod 644 {} \;
```

## Troubleshooting

### Помилка: "The [public/storage] link already exists"
```bash
# Видалити старе посилання і створити нове
rm public/storage
php artisan storage:link
```

### Зображення не завантажуються (403 Forbidden)
```bash
# Перевірити права доступу
ls -la storage/app/public/task-descriptions/
# Має бути: drwxr-xr-x

# Виправити:
sudo chmod -R 755 storage/app/public
```

### Зображення завантажуються, але не відображаються
```bash
# 1. Очистити кеш браузера
# 2. Перевірити APP_URL в .env
# 3. Перевірити що символічне посилання працює:
ls -la public/storage

# 4. Перевірити доступність через curl:
curl -I http://your-domain.com/storage/task-descriptions/image.png
```

### Зображення відображаються тільки локально
```bash
# На production переконайтеся що:
# 1. APP_URL правильний в .env
# 2. Символічне посилання створене
# 3. Права доступу правильні
# 4. Nginx/Apache налаштовані для обслуговування /storage
```

## SELinux (якщо використовується)
```bash
# Дозволити Apache/Nginx читати storage
sudo chcon -R -t httpd_sys_content_t storage/app/public
sudo chcon -R -t httpd_sys_content_t public/storage
```

## Docker (якщо використовується)
```dockerfile
# У Dockerfile:
RUN php artisan storage:link
RUN chmod -R 755 storage/app/public
```

```yaml
# У docker-compose.yml:
volumes:
  - ./storage/app/public:/var/www/html/storage/app/public
```

## Додаткова інформація

- Документація Filament: https://filamentphp.com/docs/4.x/forms/fields/rich-editor
- Документація Laravel Storage: https://laravel.com/docs/12.x/filesystem

