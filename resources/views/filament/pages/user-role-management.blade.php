<x-filament-panels::page>
    {{-- Статистика --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Користувачі --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-md bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ $stats['users'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Користувачів</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Ролі --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-md bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-success-600 dark:text-success-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ $stats['roles'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Ролей у системі</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Права --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-md bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-warning-600 dark:text-warning-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ $stats['permissions'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Прав у системі</div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Підказка --}}
    @if($stats['permissions'] === 0)
    <x-filament::section class="mb-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-info-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <span>Початкове налаштування</span>
            </div>
        </x-slot>

        <div class="space-y-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Права для ресурсів ще не згенеровані. Для початку роботи:
            </p>

            <ol class="list-decimal list-inside space-y-2 text-sm">
                <li>Натисніть <strong>"Згенерувати права"</strong> у верхній частині сторінки</li>
                <li>Після генерації призначте ролі користувачам через таблицю нижче</li>
                <li>Користувачі зможуть працювати з системою згідно своїх прав</li>
            </ol>
        </div>
    </x-filament::section>
    @endif

    {{-- Таблиця користувачів --}}
    <x-filament::section>
        <x-slot name="heading">
            Користувачі та їх ролі
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    {{-- Інформація про ролі --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Опис ролей --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <span>Опис ролей</span>
                </div>
            </x-slot>

            <div class="space-y-3">
                <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                        <div>
                            <h4 class="font-semibold text-red-800 dark:text-red-300">Супер Адмін</h4>
                            <p class="text-sm text-red-700 dark:text-red-400">Повний доступ до всіх функцій системи, включаючи управління користувачами та ролями</p>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <div>
                            <h4 class="font-semibold text-green-800 dark:text-green-300">Користувач</h4>
                            <p class="text-sm text-green-700 dark:text-green-400">Базовий доступ до панелі з можливістю перегляду та редагування тасків</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Поради --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-warning-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                    <span>Поради</span>
                </div>
            </x-slot>

            <div class="space-y-3">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <strong>Швидке призначення:</strong> Натисніть кнопку "Ролі" навпроти користувача щоб швидко змінити його права
                    </p>
                </div>

                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                    <p class="text-sm text-purple-800 dark:text-purple-300">
                        <strong>Супер адмін:</strong> Використовуйте кнопку "Зробити супер адміном" для швидкого надання повних прав
                    </p>
                </div>

                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <p class="text-sm text-amber-800 dark:text-amber-300">
                        <strong>Увага:</strong> Будьте обережні з призначенням ролі супер адміна - це надає необмежений доступ
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

