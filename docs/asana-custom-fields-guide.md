# 📋 Кастомні поля Asana для тасків

## Опис

Реалізовано повну підтримку кастомних полів (custom fields) з Asana для тасків з правильною архітектурою:

- **Кастомні поля визначаються на рівні проєкту** (`ProjectCustomField`) - це налаштування полів (назва, тип, можливі варіанти для enum)
- **Значення полів зберігаються на рівні таску** (`TaskCustomField`) - це конкретні значення для кожного таску

Така архітектура відповідає структурі Asana API, де custom fields належать проєкту, а таски мають лише значення цих полів.

---

## 🏗️ Архітектура

### Дві таблиці:

1. **`project_custom_fields`** - Налаштування кастомних полів проєкту
   - Зберігає визначення полів (назва, тип, можливі варіанти)
   - Синхронізується з `custom_field_settings` проєкту в Asana
   
2. **`task_custom_fields`** - Значення кастомних полів для тасків
   - Зберігає конкретні значення полів для кожного таску
   - Має зв'язок з `project_custom_fields` через `project_custom_field_id`

### Схема зв'язків:

```
Project (проєкт)
  └─ ProjectCustomField (налаштування полів)
       └─ TaskCustomField (значення полів)
            └─ Task (таск)
```

---

## Структура бази даних

### Таблиця `project_custom_fields`

```sql
CREATE TABLE project_custom_fields (
    id BIGINT PRIMARY KEY,
    project_id BIGINT FOREIGN KEY REFERENCES projects(id) ON DELETE CASCADE,
    asana_gid VARCHAR(255) -- Asana custom field GID
    name VARCHAR(255) -- Назва поля
    type VARCHAR(255) -- Тип: text, number, enum, date, multi_enum, people
    description TEXT NULL -- Опис поля
    enum_options JSON NULL -- Можливі варіанти для enum: [{"gid":"...", "name":"..."}]
    is_required BOOLEAN DEFAULT FALSE -- Обов'язкове поле
    precision INT NULL -- Точність для number полів
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY project_custom_field_unique (project_id, asana_gid)
);
```

### Таблиця `task_custom_fields`

```sql
CREATE TABLE task_custom_fields (
    id BIGINT PRIMARY KEY,
    task_id BIGINT FOREIGN KEY REFERENCES tasks(id) ON DELETE CASCADE,
    project_custom_field_id BIGINT NULL FOREIGN KEY REFERENCES project_custom_fields(id) ON DELETE CASCADE,
    asana_gid VARCHAR(255) -- Asana custom field GID
    name VARCHAR(255) -- Назва поля (кешована)
    type VARCHAR(255) -- Тип поля
    
    -- Значення
    text_value TEXT NULL,
    number_value DECIMAL(15,2) NULL,
    date_value DATE NULL,
    enum_value_gid VARCHAR(255) NULL,
    enum_value_name VARCHAR(255) NULL,
    multi_enum_values JSON NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY task_custom_field_unique (task_id, asana_gid)
);
```

---

## Міграція

Для застосування змін до бази даних:

```bash
php artisan migrate
```

Це створить обидві таблиці: `project_custom_fields` та `task_custom_fields`.

---

## Синхронізація

### Крок 1: Синхронізувати налаштування полів проєктів

**Спочатку** потрібно синхронізувати визначення кастомних полів з проєктів Asana:

```bash
# Синхронізувати всі проєкти
php artisan asana:sync-project-custom-fields

# Тільки один проєкт
php artisan asana:sync-project-custom-fields --project=5

# Перезаписати існуючі
php artisan asana:sync-project-custom-fields --force
```

**Приклад виводу:**
```bash
$ php artisan asana:sync-project-custom-fields

Знайдено проєктів для синхронізації: 10

 10/10 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

✓ Синхронізовано проєктів: 10
✓ Синхронізовано полів: 45

✓ Синхронізація завершена!
Тепер запустіть: php artisan asana:sync-custom-fields
```

### Крок 2: Синхронізувати значення полів тасків

**Після** синхронізації полів проєктів, синхронізуйте значення для тасків:

```bash
# Синхронізувати всі таски
php artisan asana:sync-custom-fields

# Тільки один таск
php artisan asana:sync-custom-fields --task=123

# Тільки таски з проекту
php artisan asana:sync-custom-fields --project=5

# Перезаписати існуючі
php artisan asana:sync-custom-fields --force
```

### Повна синхронізація (з нуля)

```bash
# 1. Синхронізувати налаштування полів проєктів
php artisan asana:sync-project-custom-fields --force

# 2. Синхронізувати значення полів тасків
php artisan asana:sync-custom-fields --force
```

---

## Автоматична синхронізація через Webhooks

Кастомні поля автоматично синхронізуються при:
- ✅ Створенні нового таску через Asana webhook
- ✅ Оновленні таску через Asana webhook
- ✅ Зміні значення кастомного поля в Asana

**Налаштування:**
```bash
php artisan asana:webhooks:create-all --force
```

---

## Підтримувані типи полів

| Тип | Опис | Приклад значення |
|-----|------|------------------|
| `text` | Текстове поле | "Клієнт: ТОВ Компанія" |
| `number` | Числове поле | 1500.00 |
| `date` | Дата | 24.10.2025 |
| `enum` | Список (один вибір) | "Високий" |
| `multi_enum` | Множинний вибір | ["Backend", "Frontend"] |
| `people` | Користувачі | (не реалізовано) |

---

## Робота з кастомними полями в коді

### Отримання налаштувань полів проєкту

```php
$project = Project::with('customFields')->find(1);

// Всі кастомні поля проєкту
foreach ($project->customFields as $field) {
    echo "{$field->name} ({$field->type})\n";
    
    // Для enum полів - отримати варіанти
    if ($field->type === 'enum') {
        $options = $field->getEnumOptions();
        foreach ($options as $option) {
            echo "  - {$option['name']}\n";
        }
    }
}
```

### Отримання значень полів таску

```php
$task = Task::with('customFields.projectCustomField')->find(1);

// Всі кастомні поля таску
foreach ($task->customFields as $fieldValue) {
    echo "{$fieldValue->name}: {$fieldValue->getValue()}\n";
    
    // Отримати налаштування поля через зв'язок
    $fieldSettings = $fieldValue->projectCustomField;
    if ($fieldSettings) {
        echo "  Тип: {$fieldSettings->type}\n";
        echo "  Обов'язкове: " . ($fieldSettings->is_required ? 'Так' : 'Ні') . "\n";
    }
}
```

### Фільтрація тасків за кастомним полем

```php
// Знайти таски з певним значенням enum поля
$tasks = Task::whereHas('customFields', function ($query) {
    $query->where('name', 'Пріоритет')
          ->where('enum_value_name', 'Високий');
})->get();

// Знайти таски з числовим полем більше 1000
$tasks = Task::whereHas('customFields', function ($query) {
    $query->where('name', 'Бюджет')
          ->where('number_value', '>', 1000);
})->get();
```

---

## Відображення в Filament

### Таб "Кастомні поля"

В інтерфейсі редагування таску автоматично з'являється новий таб **"Кастомні поля"**, якщо для таску є кастомні поля з Asana.

**Особливості:**
- 🔒 **Тільки для перегляду** - поля неможливо редагувати в Filament
- 🔢 **Badge з кількістю** - на табі відображається кількість кастомних полів
- 📊 **Форматування** - значення автоматично форматуються залежно від типу
- 🔗 **Зв'язок з ProjectCustomField** - використовуються налаштування з проєкту

---

## Приклади використання

### 1. Отримати всі можливі варіанти enum поля

```php
$project = Project::find(1);
$statusField = $project->customFields()
    ->where('name', 'Статус')
    ->where('type', 'enum')
    ->first();

if ($statusField) {
    $options = $statusField->getEnumOptions();
    // [
    //   ['gid' => '123', 'name' => 'Новий', 'color' => 'green'],
    //   ['gid' => '456', 'name' => 'В процесі', 'color' => 'yellow'],
    // ]
}
```

### 2. Статистика по enum полю

```php
$project = Project::find(1);

// Підрахувати кількість тасків по статусам
$stats = TaskCustomField::where('name', 'Статус')
    ->whereHas('task', function($q) use ($project) {
        $q->where('project_id', $project->id);
    })
    ->groupBy('enum_value_name')
    ->selectRaw('enum_value_name, count(*) as count')
    ->get();

foreach ($stats as $stat) {
    echo "{$stat->enum_value_name}: {$stat->count} тасків\n";
}
```

### 3. Валідація значень enum

```php
$task = Task::find(1);
$projectCustomField = ProjectCustomField::where('project_id', $task->project_id)
    ->where('name', 'Пріоритет')
    ->first();

$valueGid = '1234567890';
$option = $projectCustomField->findEnumOption($valueGid);

if ($option) {
    echo "Вибрано: {$option['name']}\n";
} else {
    echo "Невірний варіант!\n";
}
```

---

## Типові сценарії

### Початкова синхронізація після розгортання

```bash
# 1. Застосувати міграції
php artisan migrate

# 2. Створити webhooks для всіх проектів
php artisan asana:webhooks:create-all --force

# 3. Синхронізувати налаштування полів проєктів
php artisan asana:sync-project-custom-fields --force

# 4. Синхронізувати значення полів тасків
php artisan asana:sync-custom-fields --force
```

### Додавання нового проєкту

```bash
# 1. Синхронізувати налаштування полів для нового проєкту
php artisan asana:sync-project-custom-fields --project=NEW_PROJECT_ID

# 2. Синхронізувати таски цього проєкту
php artisan asana:sync-custom-fields --project=NEW_PROJECT_ID
```

---

## Troubleshooting

### Проблема: TaskCustomField не має зв'язку з ProjectCustomField

**Причина:** Поля проєкту не синхронізовані

**Рішення:**
```bash
php artisan asana:sync-project-custom-fields --force
php artisan asana:sync-custom-fields --force
```

### Проблема: Відсутні варіанти для enum полів

**Причина:** Налаштування поля не синхронізовано

**Рішення:**
```bash
php artisan asana:sync-project-custom-fields --project=PROJECT_ID
```

---

## Changelog

### v2.0.0 (24.10.2025) - BREAKING CHANGES
- ✅ **Правильна архітектура:** Розділено на `project_custom_fields` та `task_custom_fields`
- ✅ Створено таблицю `project_custom_fields` для налаштувань полів
- ✅ Додано `project_custom_field_id` до `task_custom_fields`
- ✅ Створено модель `ProjectCustomField`
- ✅ Додано команду `asana:sync-project-custom-fields`
- ✅ Оновлено команду `asana:sync-custom-fields` для використання зв'язків
- ✅ Оновлено webhook синхронізацію
- ✅ Підтримка `enum_options` з можливістю валідації

### v1.0.0 (24.10.2025)
- ✅ Початкова реалізація (застаріла)

---

## Додаткові ресурси

- **Asana API - Custom Fields:** https://developers.asana.com/docs/custom-fields
- **Asana API - Custom Field Settings:** https://developers.asana.com/docs/custom-field-settings

| Тип | Опис | Приклад |
|-----|------|---------|
| `text` | Текстове поле | "Клієнт: ТОВ Компанія" |
| `number` | Числове поле | 1500.00 |
| `date` | Дата | 24.10.2025 |
| `enum` | Список (один вибір) | "Пріоритет: Високий" |
| `multi_enum` | Множинний вибір | ["Backend", "Frontend", "Testing"] |

---

## Структура бази даних

### Таблиця `task_custom_fields`

```sql
CREATE TABLE task_custom_fields (
    id BIGINT PRIMARY KEY,
    task_id BIGINT FOREIGN KEY REFERENCES tasks(id) ON DELETE CASCADE,
    asana_gid VARCHAR(255) -- Asana custom field GID
    name VARCHAR(255) -- Назва поля
    type VARCHAR(255) -- Тип: text, number, enum, date, multi_enum
    
    -- Значення
    text_value TEXT NULL,
    number_value DECIMAL(15,2) NULL,
    date_value DATE NULL,
    enum_value_gid VARCHAR(255) NULL,
    enum_value_name VARCHAR(255) NULL,
    multi_enum_values JSON NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY task_custom_field_unique (task_id, asana_gid)
);
```

---

## Автоматична синхронізація через Webhooks

Кастомні поля автоматично синхронізуються при:
- ✅ Створенні нового таску через Asana webhook
- ✅ Оновленні таску через Asana webhook
- ✅ Зміні значення кастомного поля в Asana

**Налаштування:**
Переконайтеся, що webhooks створені для ваших проектів:
```bash
php artisan asana:webhooks:create-all --force
```

---

## Ручна синхронізація

### Команда `asana:sync-custom-fields`

Синхронізує кастомні поля з Asana для тасків.

#### Базове використання

```bash
# Синхронізувати всі таски
php artisan asana:sync-custom-fields
```

#### Опції

**`--task=ID`** - Синхронізувати тільки один таск
```bash
php artisan asana:sync-custom-fields --task=123
```

**`--project=ID`** - Синхронізувати тільки таски конкретного проекту
```bash
php artisan asana:sync-custom-fields --project=5
```

**`--force`** - Перезаписати існуючі кастомні поля
```bash
php artisan asana:sync-custom-fields --force
```

#### Приклад виводу

```bash
$ php artisan asana:sync-custom-fields

Знайдено тасків для синхронізації: 150

 150/150 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

✓ Синхронізовано: 120
○ Пропущено (без custom fields): 30

✓ Синхронізація завершена!
```

---

## Відображення в Filament

### Таб "Кастомні поля"

В інтерфейсі редагування таску автоматично з'являється новий таб **"Кастомні поля"**, якщо для таску є кастомні поля з Asana.

**Особливості:**
- 🔒 **Тільки для перегляду** - поля неможливо редагувати в Filament
- 🔢 **Badge з кількістю** - на табі відображається кількість кастомних полів
- 📊 **Форматування** - значення автоматично форматуються залежно від типу
- 🔄 **Оновлення** - підказка як оновити дані через консольну команду

**Відображення полів:**
- Назва поля (з Asana)
- Тип поля (з іконкою: 📝 Текст, 🔢 Число, 📅 Дата, 📋 Список)
- Поточне значення

---

## Робота з кастомними полями в коді

### Отримання кастомних полів таску

```php
$task = Task::with('customFields')->find(1);

// Всі кастомні поля
$customFields = $task->customFields;

// Конкретне поле за назвою
$priorityField = $task->customFields()->where('name', 'Пріоритет')->first();
if ($priorityField) {
    echo $priorityField->getValue(); // Повертає форматоване значення
}
```

### Отримання значення поля

```php
$field = TaskCustomField::find(1);

// Універсальний метод - автоматично визначає тип
$value = $field->getValue();

// Або напряму з атрибутів
match ($field->type) {
    'text' => $field->text_value,
    'number' => $field->number_value,
    'date' => $field->date_value->format('d.m.Y'),
    'enum' => $field->enum_value_name,
    'multi_enum' => $field->multi_enum_values,
};
```

### Встановлення значення з даних Asana

```php
$field = new TaskCustomField([
    'task_id' => 1,
    'asana_gid' => '1234567890',
    'name' => 'Бюджет проекту',
]);

$field->setValueFromAsana([
    'type' => 'number',
    'number_value' => 15000.50,
]);

$field->save();
```

---

## Приклади використання

### 1. Фільтрація тасків за кастомним полем

```php
// Знайти таски з певним значенням кастомного поля
$tasks = Task::whereHas('customFields', function ($query) {
    $query->where('name', 'Пріоритет')
          ->where('enum_value_name', 'Високий');
})->get();
```

### 2. Отримання всіх кастомних полів типу "число"

```php
$task = Task::find(1);
$numberFields = $task->customFields()
    ->where('type', 'number')
    ->get();

foreach ($numberFields as $field) {
    echo "{$field->name}: {$field->number_value}\n";
}
```

### 3. Виведення всіх полів у вигляді списку

```php
$task = Task::with('customFields')->find(1);

foreach ($task->customFields as $field) {
    echo "{$field->name}: {$field->getValue()}\n";
}
```

---

## Міграція

Для застосування змін до бази даних:

```bash
php artisan migrate
```

Міграція створить таблицю `task_custom_fields` з усіма необхідними індексами та зв'язками.

---

## Відкат (Rollback)

Якщо потрібно відкотити зміни:

```bash
# Відкотити останню міграцію
php artisan migrate:rollback

# Або вручну видалити таблицю
php artisan tinker
>>> Schema::dropIfExists('task_custom_fields');
```

---

## Типові сценарії

### Початкова синхронізація після розгортання

```bash
# 1. Застосувати міграції
php artisan migrate

# 2. Створити webhooks для всіх проектів
php artisan asana:webhooks:create-all --force

# 3. Синхронізувати кастомні поля для існуючих тасків
php artisan asana:sync-custom-fields --force
```

### Оновлення кастомних полів після змін в Asana

```bash
# Автоматично оновиться через webhook
# Або вручну:
php artisan asana:sync-custom-fields --force
```

### Синхронізація для конкретного проекту

```bash
# 1. Знайти ID проекту
php artisan tinker
>>> Project::select('id', 'name')->get();

# 2. Синхронізувати
php artisan asana:sync-custom-fields --project=5
```

---

## Логування

Всі операції з кастомними полями логуються:

```bash
# Перегляд логів
tail -f storage/logs/laravel.log | grep -i "custom field"
```

Приклади логів:

**Успішна синхронізація:**
```
[2025-10-24] local.INFO: Task updated from webhook {"custom_fields_count":3}
```

**Помилка:**
```
[2025-10-24] local.ERROR: Failed to sync custom fields for task {"task_id":123}
```

---

## Troubleshooting

### Проблема: Кастомні поля не відображаються

**Причина:** Таск ще не синхронізовано з Asana

**Рішення:**
```bash
php artisan asana:sync-custom-fields --task=123
```

### Проблема: Старі значення кастомних полів

**Причина:** Webhooks не працюють або дані застаріли

**Рішення:**
```bash
# Перевірити webhooks
php artisan asana:webhooks list

# Пересинхронізувати з --force
php artisan asana:sync-custom-fields --force
```

### Проблема: Помилка "column not found"

**Причина:** Міграція не застосована

**Рішення:**
```bash
php artisan migrate
php artisan migrate:status
```

---

## Обмеження та особливості

1. **Тільки читання в Filament** - кастомні поля неможливо редагувати через Filament, тільки в Asana
2. **Автоматична синхронізація** - вимагає активних webhooks
3. **Типи полів** - підтримуються всі основні типи Asana custom fields
4. **Унікальність** - одне кастомне поле може бути лише один раз для таску (task_id + asana_gid)

---

## Додаткові ресурси

- **Документація Asana API:** https://developers.asana.com/docs/custom-fields
- **Інші гайди:**
  - [asana-webhooks-production.md](./asana-webhooks-production.md)
  - [asana-integration-guide.md](./asana-integration-guide.md)
  - [asana-usage-examples.md](./asana-usage-examples.md)

---

## Changelog

### v1.0.0 (24.10.2025)
- ✅ Створено таблицю `task_custom_fields`
- ✅ Створено модель `TaskCustomField`
- ✅ Додано відношення `customFields()` до моделі `Task`
- ✅ Реалізовано автоматичну синхронізацію через webhooks
- ✅ Створено команду `asana:sync-custom-fields`
- ✅ Додано таб "Кастомні поля" в Filament
- ✅ Підтримка всіх типів полів: text, number, date, enum, multi_enum

