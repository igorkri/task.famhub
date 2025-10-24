<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * This is the model class for table "act_of_work".
 *
 * @property int $id
 * @property string $number Номер акту
 * @property string $status Статус акту
 * @property string $period Період виконання робіт
 * @property string $period_type Тип періоду
 * @property string $period_year Рік періоду
 * @property string $period_month Місяць періоду
 * @property int $user_id ID користувача
 * @property string $date Дата складання акту
 * @property string $description Опис робіт
 * @property float $total_amount Загальна сума
 * @property float $paid_amount Сума, вже сплачена
 * @property string|null $file_excel Файл Excel
 * @property string|null $created_at Дата створення
 * @property string|null $updated_at Дата оновлення
 * @property string|null $type Тип акту (наприклад, "receipt_of_funds" - надходження коштів)
 * @property string|null $telegram_status Статус Telegram
 * @property int $sort
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|ActOfWorkDetail[] $details
 * @property-read int|null $details_count
 */

class ActOfWork extends Model
{
    /** @use HasFactory<\Database\Factories\ActOfWorkFactory> */
    use HasFactory;

    const STATUS_PENDING = 'pending'; // Очікує
    const STATUS_IN_PROGRESS = 'in_progress'; // В процесі
    const STATUS_PAID = 'paid'; // Оплачено
    const STATUS_PARTIALLY_PAID = 'partially_paid'; // Частково оплачено
    const STATUS_CANCELLED = 'cancelled'; // Скасовано
    const STATUS_ARCHIVED = 'archived'; // Архівовано
    const STATUS_DRAFT = 'draft'; // Чернетка
    const STATUS_DONE = 'done'; // Превірено, оплачено

    const TELEGRAM_STATUS_SEND = 'send'; // Надіслано
    const TELEGRAM_STATUS_FAILED = 'failed'; // Помилка надсилання
    const TELEGRAM_STATUS_PENDING = 'pending'; // Очікує на надсилання

    const TYPE_ACT = 'act'; // Тип запису - акт
    const TYPE_RECEIPT_OF_FUNDS = 'receipt_of_funds'; // Тип запису - надходження коштів
    const TYPE_NEW_PROJECT = 'new_project'; // Тип запису - новий проект
    const TYPE_OTHER = 'other'; // Інший тип запису


    protected $fillable = [
        'number',
        'status',
        'period',
        'period_type',
        'period_year',
        'period_month',
        'user_id',
        'date',
        'description',
        'total_amount',
        'paid_amount',
        'file_excel',
        'sort',
        'telegram_status',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'array',
            'date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'sort' => 'integer',
        ];
    }


    public static array $statusList = [
        self::STATUS_PENDING => 'Очікує',
//        self::STATUS_IN_PROGRESS => 'В процесі',
        self::STATUS_PAID => 'Оплачено',
        self::STATUS_PARTIALLY_PAID => 'Частково оплачено',
        self::STATUS_CANCELLED => 'Скасовано',
        self::STATUS_DONE => 'Превірено, оплачено',
//        self::STATUS_ARCHIVED => 'Архівовано',
//        self::STATUS_DRAFT => 'Чернетка',
    ];


    public static array $monthsList = [
        "January" => "Січень",
        "February" => "Лютий",
        "March" => "Березень",
        "April" => "Квітень",
        "May" => "Травень",
        "June" => "Червень",
        "July" => "Липень",
        "August" => "Серпень",
        "September" => "Вересень",
        "October" => "Жовтень",
        "November" => "Листопад",
        "December" => "Грудень",
    ];

    // роки
    public static array $yearsList = [
        '2023' => '2023',
        '2024' => '2024',
        '2025' => '2025',
        '2026' => '2026',
        '2027' => '2027',
        '2028' => '2028',
        '2029' => '2029',
        '2030' => '2030',
    ];


    public static array $periodTypeList = [
        // перша половина місяця, друга половина місяця, тиждень, місяць, рік
        'year' => 'Рік',
        'first_half_month' => 'Перша половина місяця',
        'second_half_month' => 'Друга половина місяця',
        'month' => 'Місяць',
        'new_project' => 'Новий проект',
        'receipt_of_funds' => 'надходження коштів', // надходження коштів

    ];

    public static array $type = [
        self::TYPE_ACT => 'Акт',
        self::TYPE_RECEIPT_OF_FUNDS => 'Надходження коштів',
        self::TYPE_NEW_PROJECT => 'Новий проект',
        self::TYPE_OTHER => 'Інший тип',
    ];


    public static array $telegramStatusList = [
        self::TELEGRAM_STATUS_SEND => 'Надіслано',
        self::TELEGRAM_STATUS_FAILED => 'Помилка надсилання',
        self::TELEGRAM_STATUS_PENDING => 'Очікує на надсилання',
    ];

    public function getPeriodText()
    {
        $period_type = $this->period_type ? self::$periodTypeList[$this->period_type] : '⸺';
        $period_year = $this->period_year ? $this->period_year : '⸺';
        $period_month = $this->period_month ? self::$monthsList[$this->period_month] : '⸺';

        return "{$period_type} ({$period_month} {$period_year})";
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ActOfWorkDetail::class);
    }

//    public function sendTelegram()
//    {
//        if ($this->file_excel) {
//            // Удаляем домен из URL
//            $fileUrl = str_replace(Yii::$app->params['domain'], '', $this->file_excel);
//            $filePath = Yii::getAlias('@frontend/web') . $fileUrl;
//
//            if (!file_exists($filePath)) {
//                Yii::error("Файл не найден: {$filePath}", 'telegram');
//                $this->telegram_status = self::TELEGRAM_STATUS_FAILED;
//                $this->save(false, ['telegram_status']);
//                return false;
//            }
//
//            $title = "🧾 Звіт " . self::$periodTypeList[$this->period_type] . ' '
//                . self::$monthsList[$this->period_month] . ' '
//                . $this->period_year . ' дата складання: ' . $this->date . ' № ' . $this->number;
//
//            $res = Yii::$app->telegram->sendDocument($filePath, $title);
//
//            if (!$res) {
//                Yii::error("Помилка надсилання звіту №{$this->number} від {$this->date} до Telegram.", 'telegram');
//                $this->telegram_status = self::TELEGRAM_STATUS_FAILED;
//                $this->save(false, ['telegram_status']);
//                return false;
//            }
//
//            $this->telegram_status = self::TELEGRAM_STATUS_SEND;
//        } else {
//            Yii::$app->telegram->sendMessage("⚠️ Звіт відсутній! @masterokpl перевір, будь ласка, файл акту №{$this->number} від {$this->date}.");
//            $this->telegram_status = self::TELEGRAM_STATUS_FAILED;
//        }
//
//        $this->save(false, ['telegram_status']);
//        return true;
//    }
}
