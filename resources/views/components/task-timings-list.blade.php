@php
    $timings = $task?->times()->with('user')->orderByDesc('created_at')->get();
@endphp

<div style="padding: 1em;">
    <h3>Тайминги задачи</h3>
    @if($timings && $timings->count())
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f5f5f5;">
                    <th style="padding:6px; border:1px solid #ddd;">Пользователь</th>
                    <th style="padding:6px; border:1px solid #ddd;">Длительность (сек)</th>
                    <th style="padding:6px; border:1px solid #ddd;">Статус</th>
                    <th style="padding:6px; border:1px solid #ddd;">Создано</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timings as $time)
                    <tr>
                        <td style="padding:6px; border:1px solid #ddd;">{{ $time->user?->name ?? $time->user_id }}</td>
                        <td style="padding:6px; border:1px solid #ddd;">{{ $time->duration }}</td>
                        <td style="padding:6px; border:1px solid #ddd;">{{ $time->status }}</td>
                        <td style="padding:6px; border:1px solid #ddd;">{{ $time->created_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="color:#888;">Нет таймингов для этой задачи.</div>
    @endif
</div>

