<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\TaskResource;

class TaskController extends Controller
{
    public function index(Project $project)
    {
        $tasks = $project->tasks()->orderBy('order')->get();
        return TaskResource::collection($tasks);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $countTasks = Task::where('project_id', $project->id)->count();
        $validated['order'] = $countTasks + 1;
        
        $task = $project->tasks()->create($validated);

        return new TaskResource($task);
    }

    public function show(Project $project, Task $task)
    {
        return new TaskResource($task);
    }

    public function update(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $task->update($validated);

        return new TaskResource($task);
    }

    public function destroy(Project $project, Task $task)
    {
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    // Reorder tasks in a project
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.order' => 'required|integer'
        ]);

        DB::beginTransaction();
        try {
            
            foreach ($validated['tasks'] as $taskData) {
                $task = Task::find($taskData['id']);
                $task->update(['order' => $taskData['order']]);
            }

            DB::commit();
            return response()->json(['message' => 'Tasks reordered successfully']);

        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
