<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Time;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskTimerController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $user = Auth::user();
        $duration = (int) $request->input('seconds', 0);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $time = Time::updateOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $user->id,
            ],
            [
                'duration' => $duration,
            ]
        );
        return response()->json(['success' => true, 'duration' => $time->duration]);
    }

    public function show(Task $task)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $time = Time::where('task_id', $task->id)->where('user_id', $user->id)->first();
        return response()->json(['duration' => $time?->duration ?? 0]);
    }
}
