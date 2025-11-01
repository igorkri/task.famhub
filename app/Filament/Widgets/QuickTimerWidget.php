<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickTimerWidget extends Widget
{
    protected static ?string $heading = 'Швидкий трекінг часу';

    protected string $view = 'filament.widgets.quick-timer-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->check();
    }
}
