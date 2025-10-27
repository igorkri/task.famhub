# Скрипты импорта базы данных

## Доступные скрипты

### 1. db-manager.sh (Рекомендуется! 🌟)
Полнофункциональный менеджер базы данных с интерактивным меню.

**Использование:**
```bash
./db-manager.sh
```

**Особенности:**
- 🚀 Автоматически запускает Docker контейнеры при необходимости
- 📁 Интерактивный выбор SQL файла для импорта
- 📊 Просмотр статистики базы данных
- 🔄 Управление контейнерами (запуск, остановка, перезапуск)
- 📝 Просмотр логов MySQL
- 💾 Экспорт базы данных
- 🖥️ Прямой доступ к MySQL консоли
- ✅ Проверка статуса контейнеров

### 2. import-db-interactive.sh
Интерактивный скрипт с меню выбора SQL файла.

**Использование:**
```bash
./import-db-interactive.sh
```

**Особенности:**
- Показывает список всех SQL файлов в директории `storage/`
- Отображает размер и дату модификации каждого файла
- Интерактивный выбор файла
- Подтверждение перед импортом
- Показывает статистику таблиц после импорта
- Поддержка прогресс-бара (если установлен `pv`)

### 3. import-db.sh
Простой скрипт для импорта конкретного SQL файла.

**Использование:**
```bash
./import-db.sh storage/task_famhub.sql
```

**Примеры:**
```bash
# Импорт основной базы данных
./import-db.sh storage/task_famhub.sql

# Импорт резервной копии
./import-db.sh storage/igor_task.sql

# Импорт старых данных Asana
./import-db.sh storage/old-asana.sql
```

## Требования

1. Docker и docker-compose должны быть установлены
2. Контейнеры должны быть запущены:
   ```bash
   docker-compose up -d
   ```

## Проверка состояния контейнеров

```bash
# Проверить, что контейнеры запущены
docker-compose ps

# Логи MySQL контейнера
docker-compose logs db

# Подключиться к MySQL через командную строку
docker exec -it task-famhub-mysql mysql -utask_famhub -ppassword task_famhub
```

## Параметры подключения

Из `docker-compose.yml`:
- **Контейнер:** task-famhub-mysql
- **База данных:** task_famhub
- **Пользователь:** task_famhub
- **Пароль:** password
- **Root пароль:** root
- **Порт (хост):** 3307
- **Порт (контейнер):** 3306

## Доступ к phpMyAdmin

После запуска контейнеров, phpMyAdmin доступен по адресу:
- URL: http://localhost:8889
- Пользователь: root
- Пароль: root

## Экспорт базы данных

Для создания резервной копии:

```bash
# Экспорт всей базы данных
docker exec task-famhub-mysql mysqldump -utask_famhub -ppassword task_famhub > storage/backup_$(date +%Y%m%d_%H%M%S).sql

# Экспорт конкретных таблиц
docker exec task-famhub-mysql mysqldump -utask_famhub -ppassword task_famhub times tasks > storage/backup_times_tasks.sql

# Экспорт структуры без данных
docker exec task-famhub-mysql mysqldump -utask_famhub -ppassword --no-data task_famhub > storage/structure_only.sql
```

## Устранение проблем

### Контейнер не запускается
```bash
# Пересоздать контейнеры
docker-compose down
docker-compose up -d

# Проверить логи
docker-compose logs db
```

### Ошибка доступа denied
Проверьте пароли в `docker-compose.yml` и скриптах импорта.

### База данных не существует
```bash
# Создать базу данных
docker exec -it task-famhub-mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS task_famhub;"
```

### Очистка базы данных перед импортом
```bash
# Удалить все таблицы
docker exec -it task-famhub-mysql mysql -uroot -proot task_famhub -e "
SET FOREIGN_KEY_CHECKS = 0;
SET @tables = NULL;
SELECT GROUP_CONCAT(table_schema, '.', table_name) INTO @tables
FROM information_schema.tables 
WHERE table_schema = 'task_famhub';
SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
PREPARE stmt FROM @tables;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET FOREIGN_KEY_CHECKS = 1;
"
```

## Дополнительные возможности

### Установка pv для отображения прогресса

```bash
# Ubuntu/Debian
sudo apt-get install pv

# macOS
brew install pv
```

После установки `pv`, скрипт `import-db-interactive.sh` будет показывать прогресс импорта.

## SQL файлы в проекте

- **task_famhub.sql** - Основная база данных
- **igor_task.sql** - Резервная копия Igor
- **old-asana.sql** - Старые данные из Asana

## Поддержка

При возникновении проблем:
1. Проверьте логи контейнера: `docker-compose logs db`
2. Убедитесь, что контейнеры запущены: `docker-compose ps`
3. Проверьте доступ через phpMyAdmin: http://localhost:8889

