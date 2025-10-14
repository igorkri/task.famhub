<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Time;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @var Task $taskModel
 * @var User $user
 * @var Time $time
 *           TaskTimerController
 *           Контроллер для управления таймером задач через API
 */
class TaskTimerController extends Controller
{
    public function store(Request $request)
    {

        // "request":{"seconds":0,"user_id":"1","task_id":"1","time_id":null}
        $userId = $request->input('user_id');
        $taskId = $request->input('task_id');
        $seconds = (int) $request->input('seconds', 0);
        $timeId = $request->input('time_id');

        // проверяем пользователя
        $user = User::find($userId);
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // проверяем задачу
        $taskModel = Task::find($taskId);
        if (! $taskModel) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // Ищем существующую запись ТОЛЬКО по переданному time_id
        $time = null;

        if ($timeId) {
            $time = Time::where('id', $timeId)
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->where('status', Time::STATUS_IN_PROGRESS)
                ->first();
        }

        if (! $time) {
            // Создаем новую запись времени
            $time = new Time;
            $time->user_id = $userId;
            $time->task_id = $taskId;
            $time->title = 'Створено автоматично';
            $time->description = 'Створив: '.$user->name;
            $time->coefficient = Time::COEFFICIENT_STANDARD;
            $time->duration = $seconds;
            $time->status = Time::STATUS_IN_PROGRESS;
            $time->report_status = 'not_submitted';
            $time->is_archived = false;
            $time->save();
        } else {
            // обновляем duration существующей записи
            $time->duration = $seconds;
            $time->save();
        }

        return response()->json(['success' => true, 'duration' => $time->duration, 'time_id' => $time->id]);
    }

    public function show($task)
    {
        Log::info('TaskTimerController@show reached', ['task_param' => $task]);
        $taskModel = \App\Models\Task::find($task);
        if (! $taskModel) {
            Log::warning('Task not found', ['task_param' => $task]);

            return response()->json(['error' => 'Task not found'], 404);
        }
        //        $user = Auth::user();
        //        if (!$user) {
        //            Log::info('User not authenticated');
        //            return response()->json(['error' => 'Unauthorized'], 401);
        //        }
        $time = Time::where('task_id', $taskModel->id)
//            ->where('user_id', $user->id)
            ->where('status', Time::STATUS_IN_PROGRESS)
            ->first();

        return response()->json([
            'duration' => $time?->duration ?? 0,
            'time_id' => $time?->id,
        ]);
    }

    public function complete(Request $request): \Illuminate\Http\JsonResponse
    {
        $timeId = $request->input('time_id');
        $userId = $request->input('user_id');
        $taskId = $request->input('task_id');
        $seconds = (int) $request->input('seconds', 0);

        // Проверяем пользователя и задачу
        $user = User::find($userId);
        if (! $user) {
            return response()->json(['error' => 'User not found', 'message' => 'Користувача не знайдено', 'type' => 'error'], 404);
        }

        $taskModel = Task::find($taskId);
        if (! $taskModel) {
            return response()->json(['error' => 'Task not found', 'message' => 'Задачу не знайдено', 'type' => 'error'], 404);
        }

        // Пытаемся найти запись по time_id, если он передан
        $time = null;
        if ($timeId) {
            $time = Time::where('id', $timeId)
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->first(); // Убираем проверку статуса, чтобы найти любую запись
        }

        // Если не нашли по time_id, пытаемся найти активную запись in_progress
        if (! $time) {
            $time = Time::where('user_id', $userId)
                ->where('task_id', $taskId)
                ->where('status', Time::STATUS_IN_PROGRESS)
                ->first();
        }

        // Если запись найдена, обновляем её
        if ($time) {
            // Если запись уже завершена, просто возвращаем успех
            if ($time->status === Time::STATUS_COMPLETED) {
                return response()->json([
                    'success' => true,
                    'duration' => 0,
                    'time_id' => $time->id,
                    'message' => 'Вже збережено!',
                    'type' => 'success',
                ]);
            }

            // Обновляем запись
            $time->duration = $seconds;
            $time->status = Time::STATUS_COMPLETED;
            $time->save();

            return response()->json([
                'success' => true,
                'duration' => 0,
                'time_id' => $time->id,
                'message' => 'Збережено!',
                'type' => 'success',
            ]);
        }

        // Если запись не найдена, создаём новую завершённую запись
        $time = new Time;
        $time->user_id = $userId;
        $time->task_id = $taskId;
        $time->title = 'Створено автоматично';
        $time->description = 'Створив: '.$user->name;
        $time->coefficient = Time::COEFFICIENT_STANDARD;
        $time->duration = $seconds;
        $time->status = Time::STATUS_COMPLETED;
        $time->report_status = 'not_submitted';
        $time->is_archived = false;
        $time->save();

        return response()->json([
            'success' => true,
            'duration' => 0,
            'time_id' => $time->id,
            'message' => 'Створено та збережено!',
            'type' => 'success',
        ]);
    }

    public function pause(Request $request): \Illuminate\Http\JsonResponse
    {
        $timeId = $request->input('time_id');
        $userId = $request->input('user_id');
        $taskId = $request->input('task_id');
        $seconds = (int) $request->input('seconds', 0);

        // Проверяем пользователя и задачу
        $user = User::find($userId);
        if (! $user) {
            return response()->json(['error' => 'User not found', 'message' => 'Користувача не знайдено', 'type' => 'error'], 404);
        }

        $taskModel = Task::find($taskId);
        if (! $taskModel) {
            return response()->json(['error' => 'Task not found', 'message' => 'Задачу не знайдено', 'type' => 'error'], 404);
        }

        // Ищем активную запись
        $time = null;
        if ($timeId) {
            $time = Time::where('id', $timeId)
                ->where('user_id', $userId)
                ->where('task_id', $taskId)
                ->where('status', Time::STATUS_IN_PROGRESS)
                ->first();
        }

        if (! $time) {
            $time = Time::where('user_id', $userId)
                ->where('task_id', $taskId)
                ->where('status', Time::STATUS_IN_PROGRESS)
                ->first();
        }

        // Если запись найдена, обновляем только duration, НЕ меняя статус
        if ($time) {
            $time->duration = $seconds;
            $time->save();

            return response()->json([
                'success' => true,
                'duration' => $time->duration,
                'time_id' => $time->id,
                'message' => 'Призупинено!',
                'type' => 'info',
            ]);
        }

        return response()->json(['error' => 'Time entry not found', 'message' => 'Запис не знайдено', 'type' => 'error'], 404);
    }
}
