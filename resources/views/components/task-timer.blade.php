<div id="task-timer"
     data-task-id="{{ $getRecord()?->id }}"
     data-user-id="{{ auth()->id() }}"
     data-time-id="{{ $time_id($getRecord()) }}"
     style="display: flex; flex-direction: column; align-items: center; gap: 12px;">

    <div id="timer-display" style="font-size: 2em; font-family: monospace;">00:00:00</div>

    <div style="display: flex; gap: 8px;">
        <button type="button" id="timer-start" class="filament-button fi-btn timer-btn timer-btn-start" style="background: #4caf50; color: #fff;">Старт</button>
        <button type="button" id="timer-pause" class="filament-button fi-btn timer-btn timer-btn-pause" style="background: #ff9800; color: #fff; display: none;">Пауза</button>
        <button type="button" id="timer-stop" class="filament-button fi-btn timer-btn timer-btn-stop" style="background: #f44336; color: #fff; display: none;">Стоп</button>
    </div>

    <script>
        (function () {
            let timerInterval = null;
            let seconds = 0;
            let isPaused = false;
            let lastSavedMinute = 0;
            let isCompleted = false;

            const container = document.getElementById('task-timer');
            const display   = document.getElementById('timer-display');
            const startBtn  = document.getElementById('timer-start');
            const pauseBtn  = document.getElementById('timer-pause');
            const stopBtn   = document.getElementById('timer-stop');

            const taskId = container.dataset.taskId;
            const userId = container.dataset.userId;
            let timeId = container.dataset.timeId;

            // Обновление страницы без перезагрузки
            function updatePage(){
                fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContainer = doc.getElementById('task-timer');
                        const timerList = document.getElementById('timer-list');
                        if (timerList && doc.getElementById('timer-list')) {
                            timerList.innerHTML = doc.getElementById('timer-list').innerHTML;
                        }

                        if (newContainer) {
                            container.innerHTML = newContainer.innerHTML;
                            // Повторно инициализируем скрипт
                            eval(container.querySelector('script').innerText);
                        }
                    });
            }

            function updateDisplay() {
                const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
                const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
                const s = String(seconds % 60).padStart(2, '0');
                display.textContent = `${h}:${m}:${s}`;
            }

            function updateButtons() {
                if (isCompleted) {
                    startBtn.style.display = '';
                    pauseBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                    startBtn.disabled = true;
                } else if (timerInterval && !isPaused) {
                    startBtn.style.display = 'none';
                    pauseBtn.style.display = '';
                    stopBtn.style.display = '';
                } else {
                    startBtn.style.display = '';
                    pauseBtn.style.display = 'none';
                    stopBtn.style.display = timerInterval ? '' : 'none';
                }
            }

            function saveTime() {
                fetch(`/api/task/${taskId}/timer`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                });
            }

            function startTimer() {
                if (isCompleted) return;
                if (timerInterval === null) {
                    timerInterval = setInterval(() => {
                        if (!isPaused) {
                            seconds++;
                            updateDisplay();
                            if (seconds % 60 === 0 && seconds !== lastSavedMinute) {
                                saveTime();
                                lastSavedMinute = seconds;
                            }
                        }
                    }, 1000);
                }
                isPaused = false;
                saveTime();
                updateButtons();
            }

            function pauseTimer() {
                isPaused = true;
                saveTime();
                updateButtons();
            }

            function stopTimer() {
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                // Отправляем запрос на завершение
                fetch(`/api/task/${taskId}/timer/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                    isCompleted = true;
                    updateButtons();
                    updatePage();
                });
            }

            // При загрузке подтягиваем текущее накопленное время
            fetch(`/api/task/${taskId}/timer`, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
            })
                .then(r => r.json())
                .then(data => {
                    if (typeof data.duration === 'number') {
                        seconds = data.duration;
                        updateDisplay();
                    }
                    if (data.time_id) {
                        timeId = data.time_id;
                        container.dataset.timeId = timeId;
                    }
                });

            startBtn.addEventListener('click', startTimer);
            pauseBtn.addEventListener('click', pauseTimer);
            stopBtn.addEventListener('click', stopTimer);

            updateDisplay();
            updateButtons();
        })();
    </script>
</div>
