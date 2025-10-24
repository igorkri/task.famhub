<x-filament-panels::page>
    {{-- Статистика --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Проєкти --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ $stats['projects'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Проєктів з Asana</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Налаштування полів --}}
        <x-filament::section>
            <div class="flex items-center gap-3">

                <div class="flex-1">
                    <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ $stats['project_fields'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Налаштувань полів</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Значення в тасках --}}
        <x-filament::section>
            <div class="flex items-center gap-3">

                <div class="flex-1">
                    <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['task_fields']) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Значень в тасках</div>
                </div>
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

    {{-- Таблиця Filament з кастомними полями --}}
    <x-filament::section>
        <x-slot name="heading">
            Кастомні поля по проєктах
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    {{-- Додаткова інформація --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
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

