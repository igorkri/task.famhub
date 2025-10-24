<x-filament-panels::page>
    {{-- Статистика --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-primary-600">{{ $stats['projects'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Проєктів</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-success-600">{{ $stats['project_fields'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Налаштувань полів</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-info-600">{{ $stats['task_fields'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Значень в тасках</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Інструкція --}}
    @if($stats['project_fields'] === 0)
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-information-circle class="w-5 h-5 text-info-500" />
                <span>Швидкий старт</span>
            </div>
        </x-slot>

        <div class="space-y-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Кастомні поля ще не синхронізовані. Для початку роботи:
            </p>

            <ol class="list-decimal list-inside space-y-2 text-sm">
                <li>Натисніть <strong>"Синхронізувати поля проєктів"</strong> - завантажить налаштування полів з Asana</li>
                <li>Потім натисніть <strong>"Синхронізувати значення тасків"</strong> - завантажить значення для кожного таску</li>
                <li>Після цього поля будуть автоматично синхронізуватися через webhooks</li>
            </ol>
        </div>
    </x-filament::section>
    @endif

    {{-- Список проєктів і їх полів --}}
    <x-filament::section>
        <x-slot name="heading">
            Кастомні поля по проєктах
        </x-slot>

        <div class="space-y-4">
            @forelse($projectsData as $project)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                📁 {{ $project['name'] }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                ID: {{ $project['id'] }}
                            </p>
                        </div>

                        <div class="text-right">
                            @if($project['has_fields'])
                                <div class="text-sm">
                                    <span class="font-medium text-success-600">{{ $project['fields_count'] }}</span>
                                    <span class="text-gray-600">{{ Str::plural('поле', $project['fields_count']) }}</span>
                                </div>
                                @if($project['task_values_count'] > 0)
                                    <div class="text-xs text-gray-500">
                                        {{ $project['task_values_count'] }} значень у тасках
                                    </div>
                                @endif
                            @else
                                <span class="text-xs text-gray-400">Немає полів</span>
                            @endif
                        </div>
                    </div>

                    @if($project['has_fields'])
                        <div class="space-y-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                            @foreach($project['fields'] as $field)
                                <div class="flex items-start gap-3 p-2 bg-gray-50 dark:bg-gray-800 rounded">
                                    <span class="text-xl">
                                        @switch($field['type'])
                                            @case('enum')
                                                📋
                                                @break
                                            @case('number')
                                                🔢
                                                @break
                                            @case('text')
                                                📝
                                                @break
                                            @case('date')
                                                📅
                                                @break
                                            @default
                                                ⚙️
                                        @endswitch
                                    </span>

                                    <div class="flex-1">
                                        <div class="font-medium text-sm text-gray-900 dark:text-white">
                                            {{ $field['name'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Тип: {{ $field['type'] }}
                                        </div>

                                        @if($field['type'] === 'enum' && !empty($field['enum_options']))
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach($field['enum_options'] as $option)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                        {{ $option['name'] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500 italic">
                            Немає синхронізованих кастомних полів
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    Проєкти не знайдено
                </div>
            @endforelse
        </div>
    </x-filament::section>

    {{-- Додаткова інформація --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-light-bulb class="w-5 h-5 text-warning-500" />
                <span>Корисна інформація</span>
            </div>
        </x-slot>

        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <p>
                <strong>Що таке кастомні поля?</strong><br>
                Це додаткові поля які ви створили в Asana (Приоритет, Час план/факт, тощо). Вони автоматично синхронізуються і відображаються в тасках.
            </p>

            <p>
                <strong>Автоматична синхронізація:</strong><br>
                Після першої синхронізації, всі зміни в Asana автоматично передаються через webhooks.
            </p>

            <p>
                <strong>Де побачити?</strong><br>
                Відкрийте будь-який таск → вкладка "Кастомні поля" (якщо є кастомні поля для цього проєкту).
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>

