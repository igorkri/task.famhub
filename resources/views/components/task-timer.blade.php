<div id="task-timer" data-task-id="{{ $getRecord()?->id ?? request()->route('record') }}">
    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
        <div id="timer-display" style="font-size: 2em; font-family: monospace;">00:00:00</div>
        <div style="display: flex; gap: 8px;">
            <button type="button" id="timer-start" class="filament-button">Старт</button>
            <button type="button" id="timer-pause" class="filament-button">Пауза</button>
            <button type="button" id="timer-stop" class="filament-button">Стоп</button>
        </div>
    </div>
    <script>
        let timerInterval = null;
        let seconds = 0;
        let isPaused = false;
        let lastSavedMinute = 0;
        const display = document.getElementById('timer-display');
        const startBtn = document.getElementById('timer-start');
        const pauseBtn = document.getElementById('timer-pause');
        const stopBtn = document.getElementById('timer-stop');
        const taskId = document.getElementById('task-timer').dataset.taskId;

        function updateDisplay() {
            const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            display.textContent = `${h}:${m}:${s}`;
        }

        function saveTime() {
            fetch(`/api/task/${taskId}/timer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                },
                body: JSON.stringify({ seconds }), // duration = seconds
            });
        }

        function startTimer() {
            if (timerInterval) return;
            isPaused = false;
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

        function pauseTimer() {
            isPaused = true;
        }

        function stopTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            saveTime();
        }

        // Получение накопленного времени при загрузке
        fetch(`/api/task/${taskId}/timer`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (typeof data.duration === 'number') {
                seconds = data.duration;
                updateDisplay();
            }
        });

        startBtn.addEventListener('click', startTimer);
        pauseBtn.addEventListener('click', pauseTimer);
        stopBtn.addEventListener('click', stopTimer);
        updateDisplay();
    </script>
</div>
