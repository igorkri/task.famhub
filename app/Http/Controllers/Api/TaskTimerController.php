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
 * TaskTimerController
 * Контроллер для управления таймером задач через API
 * @package App\Http\Controllers\Api
 *
*/
class TaskTimerController extends Controller
{
    public function store(Request $request)
    {

        // "request":{"seconds":0,"user_id":"1","task_id":"1","time_id":null}
        $userId = $request->input('user_id');
        $taskId = $request->input('task_id');
        $seconds = (int)$request->input('seconds', 0);
        $timeId = $request->input('time_id');

        // проверяем пользователя
        $user = User::where('id', $userId)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        // проверяем задачу
        $taskModel = Task::where('id', $taskId)->first();
        if (!$taskModel) {
            return response()->json(['error' => 'Task not found'], 404);
        }
        // проверяем time если передан time_id
        $time = null;
        if ($timeId) {
            $timeQ = Time::where('user_id', $userId)->where('task_id', $taskId)->where('status', Time::STATUS_IN_PROGRESS);
            if ($timeId) {
                $timeQ->where('id', $timeId);
            }
            $time = $timeQ->first();
        }
        if (!$time) {
            // создаем новую запись времени
            $time = new Time();
            $time->user_id = $userId;
            $time->task_id = $taskId;
            $time->title = 'Створено автоматично'; // можно потом изменить
            $time->description = 'Створив: ' . User::where('id', $userId)->value('name'); // можно потом изменить
            $time->coefficient = Time::COEFFICIENT_STANDARD;
            $time->duration = $seconds; // сразу устанавливаем duration
            $time->status = Time::STATUS_IN_PROGRESS;
            $time->report_status = 'not_submitted';
            $time->is_archived = false;
            $time->save();
        } else {
            // обновляем duration
            $time->duration = $seconds;
            $time->status = Time::STATUS_IN_PROGRESS;
            $time->save();
        }

        return response()->json(['success' => true, 'duration' => $time->duration, 'time_id' => $time->id]);
    }

    public function show($task)
    {
        Log::info('TaskTimerController@show reached', ['task_param' => $task]);
        $taskModel = \App\Models\Task::find($task);
        if (!$taskModel) {
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
        $seconds = (int)$request->input('seconds', 0);

        $time = Time::where('id', $timeId)
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('status', Time::STATUS_IN_PROGRESS)
            ->first();
        if (!$time) {
            return response()->json(['error' => 'Time entry not found or already completed'], 404);
        }
        $time->duration = $seconds;
        $time->status = Time::STATUS_COMPLETED;
        $time->save();
        return response()->json(['success' => true, 'duration' => 0, 'time_id' => $time->id]);
    }
}
