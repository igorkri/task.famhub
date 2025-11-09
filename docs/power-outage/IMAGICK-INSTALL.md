# Встановлення Imagick для підтримки кирилиці

## Проблема

Для відображення кирилиці на зображеннях потрібно розширення **Imagick** (ImageMagick для PHP).

## Встановлення Imagick

### Крок 1: Встановити ImageMagick

```bash
sudo apt-get update
sudo apt-get install imagemagick
```

### Крок 2: Встановити PHP розширення Imagick

```bash
sudo apt-get install php-imagick
```

Або для конкретної версії PHP (наприклад, 8.3):

```bash
sudo apt-get install php8.3-imagick
```

### Крок 3: Перезавантажити PHP

```bash
sudo systemctl restart php8.3-fpm
# або
sudo systemctl restart apache2
```

### Крок 4: Перевірити

```bash
php -m | grep imagick
```

Має вивести: `imagick`

## Тестування

```bash
php artisan power:notify
```

Перевірте Telegram - текст має бути кирилицею!

## Якщо не можна встановити Imagick

Якщо немає прав sudo або не можна встановити Imagick, використовуйте попередню версію з GD та латинським текстом.

### Відновити GD версію:

```bash
git checkout app/Services/PowerOutageImageGenerator.php
```

Або використовуйте `PowerOutageImageGenerator_GD.php` backup.

## Переваги Imagick

✅ Підтримка кирилиці  
✅ Кращі шрифти (TrueType)  
✅ Краща якість зображення  
✅ Більше можливостей  

## Команди для встановлення (повна послідовність)

```bash
# 1. Встановити ImageMagick та Imagick
sudo apt-get update
sudo apt-get install -y imagemagick php-imagick

# 2. Перезапустити PHP
sudo systemctl restart php8.3-fpm || sudo systemctl restart php-fpm || sudo service apache2 restart

# 3. Перевірити
php -m | grep imagick

# 4. Протестувати
php artisan power:notify

# 5. Перевірити логи
tail -f storage/logs/laravel.log
```

## Troubleshooting

### "Class 'Imagick' not found"

Imagick не встановлено. Встановіть за інструкцією вище.

### "Font not found"

Встановіть DejaVu шрифти:

```bash
sudo apt-get install fonts-dejavu-core
```

### Зображення генерується але текст кракозябри

Перевірте що шрифт підтримує кирилицю:

```bash
fc-list | grep DejaVu
```

---

**Після встановлення Imagick всі тексти будуть українською!** 🎉

