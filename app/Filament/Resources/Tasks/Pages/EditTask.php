<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\AsanaService;
use Asana\Errors\AsanaError;
use Carbon\Carbon;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;
    protected static bool $hasStickyFooter = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromAsana')
                ->label('Получить из Asana')
                ->color('secondary')
                ->action('syncFromAsana'),
            Action::make('syncToAsana')
                ->label('Отправить в Asana')
                ->color('primary')
                ->action('syncToAsana'),
            DeleteAction::make(),
        ];
    }

    public function syncFromAsana(): void
    {
        $service = app(AsanaService::class);
        try {
            $data = $service->getTaskDetails($this->record->gid);
            $this->record->update([
                'title' => $data['name'] ?? $this->record->title,
                'description' => $data['notes'] ?? $this->record->description,
                'is_completed' => $data['completed'] ?? $this->record->is_completed,
                'deadline' => $data['due_on'] ?? $this->record->deadline,
            ]);
            Notification::make()
                ->success()
                ->title('Синхронізація з Asana успішна')
                ->send();
            $this->refresh();
        } catch (AsanaError $e) {
            Notification::make()
                ->danger()
                ->title('Помилка синхронізації з Asana')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function syncToAsana(): void
    {
        $service = app(AsanaService::class);
        $payload = [
            'name' => $this->record->title,
            'notes' => $this->record->description ?? '',
            'completed' => (bool) $this->record->is_completed,
        ];
        // Include due_on only when deadline is set and formatted as YYYY-MM-DD
        if ($this->record->deadline) {
            $deadline = $this->record->deadline;
            try {
                $date = Carbon::parse($deadline)->toDateString();
                $payload['due_on'] = $date;
            } catch (\Exception $e) {
                // Skip invalid date
            }
        }
        try {
            $service->updateTask($this->record->gid, $payload);
            Notification::make()
                ->success()
                ->title('Дані відправлені в Asana успішно')
                ->send();
        } catch (AsanaError $e) {
            Notification::make()
                ->danger()
                ->title('Помилка відправки в Asana')
                ->body($e->getMessage())
                ->send();
        }
    }
}
