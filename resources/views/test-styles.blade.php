<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Styles</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">🎨 Тест стилів</h1>
        
        <!-- Test basic Tailwind classes -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Базові класи Tailwind</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-blue-100 p-4 rounded-lg">
                    <div class="text-lg font-bold text-blue-800">Синій блок</div>
                    <div class="text-sm text-blue-600">bg-blue-100, text-blue-800</div>
                </div>
                <div class="bg-green-100 p-4 rounded-lg">
                    <div class="text-lg font-bold text-green-800">Зелений блок</div>
                    <div class="text-sm text-green-600">bg-green-100, text-green-800</div>
                </div>
                <div class="bg-amber-100 p-4 rounded-lg">
                    <div class="text-lg font-bold text-amber-800">Жовтий блок</div>
                    <div class="text-sm text-amber-600">bg-amber-100, text-amber-800</div>
                </div>
            </div>
        </div>

        <!-- Test our custom timer styles -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">🕐 Стилі таймера</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">05:42:15</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">⏱️ Загальний час</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">(342 хвилин)</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">12</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">📝 Записів</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">облік часу</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">3г 20хв</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">✅ Завершено</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">65% від загального</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">1г 45хв</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">🔄 В процесі</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">35% від загального</div>
                </div>
            </div>

            <!-- Progress bar test -->
            <div class="mt-4">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">Розподіл за статусами</div>
                <div class="flex h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="bg-emerald-500" style="width: 65%" title="Завершено"></div>
                    <div class="bg-amber-500" style="width: 35%" title="В процесі"></div>
                </div>
            </div>
        </div>

        <!-- Test dark mode toggle -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">🌙 Темна тема</h2>
            <button onclick="toggleDarkMode()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                Перемкнути тему
            </button>
            <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <div class="text-gray-800 dark:text-gray-200">
                    Цей текст повинен змінювати колір в темній темі
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
        }
    </script>
</body>
</html>