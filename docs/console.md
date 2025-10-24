```

```

В Laravel создание консольных команд (скриптов, которые запускаются через `php artisan`) очень просто. Ниже — пошаговая инструкция.

---

## 🛠️ 1. Создание консольной команды

```bash
php artisan make:command TestCommand
```

После этого будет создан файл:

```
app/Console/Commands/TestCommand.php
```

---

## 🧩 2. Структура команды

Открой `app/Console/Commands/TestCommand.php`. Там будет примерно такое:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'test:run'; // Команда в CLI

    protected $description = 'Описание команды';

    public function handle()
    {
        // Тут логика команды
        $this->info('Команда выполнена!');
    }
}
```

---

## 🧪 3. Запуск команды

```bash
php artisan test:run
```

---

## 🔁 4. Передача аргументов и опций

### Аргументы:

```php
protected $signature = 'greet {name}';
```

```php
public function handle()
{
    $name = $this->argument('name');
    $this->info("Привет, $name!");
}
```

Пример запуска:

```bash
php artisan greet Игорь
```

### Опции:

```php
protected $signature = 'greet {name} {--yell}';
```

```php
public function handle()
{
    $name = $this->argument('name');
    $text = "Привет, $name!";
  
    if ($this->option('yell')) {
        $text = strtoupper($text);
    }

    $this->info($text);
}
```

Запуск с флагом:

```bash
php artisan greet Игорь --yell
```

---

## 📅 5. Добавление в планировщик (по крону)

Открыть `app/Console/Kernel.php`, метод `schedule()`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('test:run')->daily();
}
```

Для выполнения планировщика нужен крон:

```bash
* * * * * php /path-to-your-project/artisan schedule:run >> /dev/null 2>&1
```

---

## 📁 6. Где Laravel регистрирует команды?

Автоматически сканирует все классы из `app/Console/Commands`, если они наследуют `Illuminate\Console\Command`.

Также можно вручную зарегистрировать в `Kernel.php`, метод `commands()`:

```php
protected function commands()
{
    $this->load(__DIR__.'/Commands');
}
```

Обычно это уже настроено.

---

## ✅ Пример полной команды

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearTempFiles extends Command
{
    protected $signature = 'cleanup:temp';
    protected $description = 'Удаляет временные файлы';

    public function handle()
    {
        $count = 0;
        $path = storage_path('app/temp');

        foreach (glob("$path/*.tmp") as $file) {
            unlink($file);
            $count++;
        }

        $this->info("Удалено $count временных файлов.");
    }
}
```

---

Если хочешь, могу сразу сгенерировать полезную команду под твой случай. Напиши, что именно хочешь выполнять в скрипте.

Да, в Laravel ты можешь реализовать **несколько "экшенов" внутри одной консольной команды**, **аналогично `Yii2`**, добавив:

* **аргумент или опцию**, которая определяет, что именно выполнять (`импорт новостей`, `импорт категорий` и т.п.),
* или — разделив каждую логику по методам внутри команды.

---

## ✅ Пример: Одна команда с несколькими действиями

```bash
php artisan make:command ImportContent
```

### `app/Console/Commands/ImportContent.php`

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportContent extends Command
{
    protected $signature = 'import:content {type : Тип импорта (news, categories)}';
    protected $description = 'Импорт новостей или категорий из второй БД';

    public function handle()
    {
        $type = $this->argument('type');

        match ($type) {
            'news' => $this->importNews(),
            'categories' => $this->importCategories(),
            default => $this->error("Неизвестный тип: $type"),
        };
    }

    protected function importNews()
    {
        $news = DB::connection('second')->table('news')->get();

        foreach ($news as $item) {
            // Например, импортируем в основную БД
            DB::table('news')->updateOrInsert(
                ['external_id' => $item->id],
                ['title' => $item->title, 'body' => $item->body]
            );
        }

        $this->info("Импортировано новостей: " . count($news));
    }

    protected function importCategories()
    {
        $categories = DB::connection('second')->table('news_categories')->get();

        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(
                ['external_id' => $cat->id],
                ['name' => $cat->name]
            );
        }

        $this->info("Импортировано категорий: " . count($categories));
    }
}
```

---

## 🔄 Примеры вызова

```bash
php artisan import:content news
php artisan import:content categories
```

---

## 🧠 Альтернатива — использовать подпроцессы

Можно разделить на команды:

* `php artisan import:news`
* `php artisan import:categories`

И вызывать их, например, через одну обобщающую команду:

```php
Artisan::call('import:news');
Artisan::call('import:categories');
```

---

Хочешь — могу сразу сгенерировать команду под твою структуру, если дашь структуру таблиц или минимум примеров.

---

# Консольные команды в проекте

## Миграции

Для создания миграции используйте команду:

```bash
php artisan make:migration create_table_name
```

Миграции создаются в папке `database/migrations`.

---

## Модели

Для создания модели используйте команду:

```bash
php artisan make:model ModelName
```

Модели создаются в папке `app/Models`.

---

## Filament

Для работы с Filament используйте команды:

```bash
php artisan make:filament-resource ResourceName
```

Эти команды создают ресурсы в папке `app/Filament`.
