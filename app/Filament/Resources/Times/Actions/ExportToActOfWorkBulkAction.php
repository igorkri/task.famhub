<?php

namespace App\Filament\Resources\Times\Actions;

use App\Models\ActOfWork;
use App\Models\ActOfWorkDetail;
use App\Models\Time;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ExportToActOfWorkBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('exportToActOfWork')
            ->label('Експортувати в Акт робіт')
            ->icon('heroicon-o-document-text')
            ->color('success')
            ->requiresConfirmation()
            ->form([
                TextInput::make('number')
                    ->label('Номер акту')
                    ->required()
                    ->default(fn () => 'ACT-'.date('Ymd').'-'.rand(100, 999))
                    ->maxLength(255),

                Select::make('status')
                    ->label('Статус')
                    ->options(ActOfWork::$statusList)
                    ->default(ActOfWork::STATUS_PENDING)
                    ->required(),

                Select::make('period_type')
                    ->label('Тип періоду')
                    ->options(ActOfWork::$periodTypeList)
                    ->default('month')
                    ->required(),

                Select::make('period_year')
                    ->label('Рік')
                    ->options(ActOfWork::$yearsList)
                    ->default(date('Y'))
                    ->required(),

                Select::make('period_month')
                    ->label('Місяць')
                    ->options(ActOfWork::$monthsList)
                    ->default(date('F'))
                    ->required(),

                Select::make('user_id')
                    ->label('Користувач')
                    ->relationship('user', 'name')
                    ->default(auth()->id())
                    ->required(),

                DatePicker::make('date')
                    ->label('Дата складання акту')
                    ->default(now())
                    ->required(),

                Textarea::make('description')
                    ->label('Опис робіт')
                    ->rows(3)
                    ->maxLength(65535),
            ])
            ->action(function (Collection $records, array $data) {
                // Перевіряємо, чи всі записи не експортовані раніше
                $alreadyExported = $records->filter(function ($record) {
                    return $record->status === Time::STATUS_EXPORT_AKT;
                });

                if ($alreadyExported->isNotEmpty()) {
                    Notification::make()
                        ->warning()
                        ->title('Увага!')
                        ->body('Деякі записи вже експортовані в акти роботи')
                        ->send();
                }

                // Підрахунок загальної суми
                $totalAmount = $records->sum(function ($record) {
                    // Використовуємо accessor для обчислення суми
                    return (($record->duration / 3600) * $record->coefficient) * Time::PRICE;
                });

                // Створюємо акт роботи
                $actOfWork = ActOfWork::create([
                    'number' => $data['number'],
                    'status' => $data['status'],
                    'period' => [
                        'type' => $data['period_type'],
                        'year' => $data['period_year'],
                        'month' => $data['period_month'],
                    ],
                    'period_type' => $data['period_type'],
                    'period_year' => $data['period_year'],
                    'period_month' => $data['period_month'],
                    'user_id' => $data['user_id'],
                    'date' => $data['date'],
                    'description' => $data['description'] ?? '',
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'type' => ActOfWork::TYPE_ACT,
                ]);

                // Створюємо деталі акту для кожного запису
                foreach ($records as $record) {
                    // Обчислюємо суму для кожного запису
                    $amount = (($record->duration / 3600) * $record->coefficient) * Time::PRICE;

                    ActOfWorkDetail::create([
                        'act_of_work_id' => $actOfWork->id,
                        'time_id' => $record->id,
                        'task_gid' => $record->task?->gid ?? null,
                        'project_gid' => $record->task?->project?->gid ?? null,
                        'project' => $record->task?->project?->name ?? '',
                        'task' => $record->title,
                        'description' => $record->description ?? '',
                        'amount' => $amount,
                        'hours' => $record->duration / 3600,
                    ]);

                    // Оновлюємо статус запису часу
                    $record->update([
                        'status' => Time::STATUS_EXPORT_AKT,
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title('Успішно експортовано!')
                    ->body("Створено акт роботи №{$actOfWork->number} з {$records->count()} записами")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
