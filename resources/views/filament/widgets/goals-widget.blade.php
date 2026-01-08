<x-filament-widgets::widget>
    <x-filament::section heading="Цілі та прогрес">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach ($goals as $goal)
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">{{ $goal['title'] }}</span>
                        <span class="font-bold {{ $goal['color'] === 'success' ? 'text-green-600' : ($goal['color'] === 'warning' ? 'text-amber-600' : 'text-red-600') }}">{{ $goal['progress'] }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $goal['color'] === 'success' ? 'bg-green-500' : ($goal['color'] === 'warning' ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min($goal['progress'], 100) }}%"></div>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ $goal['current'] }} / {{ $goal['target'] }} {{ $goal['unit'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-sm">
            <div class="flex gap-4 text-gray-500">
                <span>📅 {{ $summary['workingDaysPassed'] }}/{{ $summary['workingDaysInMonth'] }} днів</span>
                <span>⏱ План: {{ $summary['expectedHoursToDate'] }} год.</span>
            </div>
            @if ($summary['onTrack'])
                <span class="text-green-600 font-medium">✓ За планом</span>
            @else
                <span class="text-amber-600 font-medium">⚠ -{{ abs($summary['behindBy']) }} год.</span>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

