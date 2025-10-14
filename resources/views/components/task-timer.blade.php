<div id="task-timer"
     data-task-id="{{ $getRecord()?->id }}"
     data-user-id="{{ auth()->id() }}"
     data-time-id=""
     style="display: flex; flex-direction: column; align-items: center; gap: 12px;">

    <div id="timer-display" style="font-size: 2em; font-family: monospace;">00:00:00</div>

    <div style="display: flex; gap: 8px;">
        <button type="button" id="timer-start" class="timer-btn timer-btn-start"
                style="background: #4caf50; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
            Старт
        </button>
        <button type="button" id="timer-pause" class="timer-btn timer-btn-pause"
                style="background: #ff9800; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; display: none;">
            Пауза
        </button>
        <button type="button" id="timer-stop" class="timer-btn timer-btn-stop"
                style="background: #f44336; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; display: none;">
            Стоп
        </button>
    </div>

    <script>
        (function () {
            let timerInterval = null;
            let seconds = 0;
            let isPaused = false;
            let lastSavedMinute = 0;

            const container = document.getElementById('task-timer');
            const display   = document.getElementById('timer-display');
            const startBtn  = document.getElementById('timer-start');
            const pauseBtn  = document.getElementById('timer-pause');
            const stopBtn   = document.getElementById('timer-stop');

            const taskId = container.dataset.taskId;
            const userId = container.dataset.userId;
            let timeId = null;

            function notify(message, type = 'success') {
                if (window.FilamentNotification) {
                    new FilamentNotification()
                        .title(message)
                        .body('')
                        .icon('heroicon-o-clock')
                        .iconColor(type)
                        .send();
                } else {
                    console.log(message);
                }
            }

            function updateDisplay() {
                const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
                const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
                const s = String(seconds % 60).padStart(2, '0');
                display.textContent = `${h}:${m}:${s}`;
            }

            function updateButtons() {
                if (timerInterval && !isPaused) {
                    // Таймер работает
                    startBtn.style.display = 'none';
                    pauseBtn.style.display = '';
                    stopBtn.style.display = '';
                } else if (isPaused || (timeId && seconds > 0)) {
                    // Таймер на паузе или есть сохраненная запись
                    startBtn.style.display = '';
                    startBtn.textContent = 'Продовжити';
                    pauseBtn.style.display = 'none';
                    stopBtn.style.display = '';
                } else {
                    // Таймер остановлен
                    startBtn.style.display = '';
                    startBtn.textContent = 'Старт';
                    pauseBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                }
            }

            function saveTime() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    return;
                }

                fetch(`/api/task/${taskId}/timer`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        seconds,
                        user_id: userId,
                        task_id: taskId,
                        time_id: timeId,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.time_id && !timeId) {
                        timeId = data.time_id;
                        container.dataset.timeId = timeId;
                    }
                })
                .catch(err => console.error('Error saving time:', err));
            }

            function startTimer() {
                if (isPaused || (timeId && seconds > 0)) {
                    // Продолжаем после паузы
                    isPaused = false;

                    if (timerInterval === null) {
                        timerInterval = setInterval(() => {
                          seconds++;
                          updateDisplay();
                          // Автосохранение каждую минуту
                          if (seconds % 60 === 0 && seconds !== lastSavedMinute) {
                            saveTime();
                            lastSavedMinute = seconds;
                          }
                        }, 1000);
                    }

                    updateButtons();
                    return;
                }

                // Новый старт
                if (timerInterval === null) {
                    timerInterval = setInterval(() => {
                        if (!isPaused) {
                            seconds++;
                            updateDisplay();
                            // Автосохранение каждую минуту
                            if (seconds % 60 === 0 && seconds !== lastSavedMinute) {
                                saveTime();
                                lastSavedMinute = seconds;
                            }
                        }
                    }, 1000);
                }

                saveTime();
                updateButtons();
            }

            function pauseTimer() {
                isPaused = true;

                // Останавливаем интервал
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    return;
                }

                // Отправляем запрос на паузу
                fetch(`/api/task/${taskId}/timer/pause`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        time_id: timeId,
                        user_id: userId,
                        task_id: taskId,
                        seconds: seconds,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        notify(data.message, data.type);
                    }
                })
                .catch(err => console.error('Error pausing timer:', err));

                updateButtons();
            }

            function stopTimer() {
                // Останавливаем интервал
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                isPaused = false;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    return;
                }

                // Отправляем запрос на завершение
                fetch(`/api/task/${taskId}/timer/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        time_id: timeId,
                        user_id: userId,
                        task_id: taskId,
                        seconds: seconds,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    notify(data.message, data.type);

                    // Сбрасываем таймер
                    seconds = 0;
                    timeId = null;
                    container.dataset.timeId = '';
                    updateDisplay();
                    updateButtons();

                    // Перезагружаем страницу через 1 секунду для обновления всех данных
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                })
                .catch(err => {
                    console.error('Error stopping timer:', err);
                    notify('Помилка при зупинці таймера', 'danger');
                });
            }

            // Загружаем активную запись при инициализации
            function loadActiveTimer() {
                fetch(`/api/task/${taskId}/timer`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                })
                .then(r => r.json())
                .then(data => {
                    if (data.time_id && data.duration > 0) {
                        // Есть активная запись in_progress
                        timeId = data.time_id;
                        seconds = data.duration;
                        container.dataset.timeId = timeId;
                        isPaused = true; // Считаем что на паузе
                        updateDisplay();
                        updateButtons();
                        console.log('Loaded active timer:', { timeId, seconds });
                    } else {
                        // Нет активной записи
                        updateButtons();
                    }
                })
                .catch(err => {
                    console.error('Error loading timer:', err);
                    updateButtons();
                });
            }

            startBtn.addEventListener('click', startTimer);
            pauseBtn.addEventListener('click', pauseTimer);
            stopBtn.addEventListener('click', stopTimer);

            updateDisplay();
            loadActiveTimer(); // Загружаем активный таймер при инициализации
        })();
    </script>
</div>
