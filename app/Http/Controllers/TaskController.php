<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::orderBy('created_at', 'desc')->get();
        return view('tasks.index', compact('tasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
        ]);

        $exists = Task::where('title', $request->title)->exists();
        if ($exists) {
            return response()->json([
                'error' => 'Task already exists!'
            ], 422);
        }

        $task = Task::create([
            'title' => $request->title,
            'completed' => false,
        ]);

        return response()->json($task);
    }

    public function update(Request $request, Task $task)
    {
        $task->update([
            'completed' => $request->completed
        ]);

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['success' => true]);
    }

    public function all()
    {
        $tasks = Task::orderBy('created_at', 'desc')->get();
        return response()->json($tasks);
    }

    public function active()
    {
        $tasks = Task::where('completed', false)->orderBy('created_at', 'desc')->get();
        return response()->json($tasks);
    }
}