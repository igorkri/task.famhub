В Laravel можно легко подключить вторую (и неограниченное количество) базу данных. Вот **пошаговая инструкция**, включая пример использования в консольной команде:

---

## 🔧 1. Подключаем вторую базу данных

Открой файл `.env` и добавь параметры для второй базы:

```dotenv
DB_SECOND_HOST=127.0.0.1
DB_SECOND_PORT=3306
DB_SECOND_DATABASE=second_db
DB_SECOND_USERNAME=root
DB_SECOND_PASSWORD=secret
```

Теперь в `config/database.php` добавь новый блок в массив `connections`:

```php
'second' => [
    'driver' => 'mysql',
    'host' => env('DB_SECOND_HOST', '127.0.0.1'),
    'port' => env('DB_SECOND_PORT', '3306'),
    'database' => env('DB_SECOND_DATABASE', 'forge'),
    'username' => env('DB_SECOND_USERNAME', 'forge'),
    'password' => env('DB_SECOND_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
],
```

---

## 🧪 2. Пример использования в консольной команде

Создаем команду:

```bash
php artisan make:command TestSecondDb
```

Внутри `app/Console/Commands/TestSecondDb.php`:

```php
use Illuminate\Support\Facades\DB;

class TestSecondDb extends Command
{
    protected $signature = 'test:second-db';
    protected $description = 'Тестовое подключение ко второй БД';

    public function handle()
    {
        $results = DB::connection('second')->select('SELECT NOW() as current_time');

        foreach ($results as $row) {
            $this->info("Текущее время на второй БД: " . $row->current_time);
        }
    }
}
```

---

## 📦 3. Работа с моделями во второй БД

Если у тебя есть модель, и ты хочешь, чтобы она работала с другой БД:

```php
class ExternalUser extends Model
{
    protected $connection = 'second';
    protected $table = 'users'; // если нужно
}
```

Теперь можно:

```php
$users = ExternalUser::all();
```

---

## ⚠️ Проверка

Проверь подключение:

```bash
php artisan test:second-db
```

Если есть ошибка — Laravel покажет причину (например, неверный логин или база).

---

Если нужно — могу сразу подготовить рабочий шаблон миграции или модель под вторую базу.
