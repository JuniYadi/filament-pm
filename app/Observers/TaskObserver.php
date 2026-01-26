<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskObserver
{
    public function created(Task $task): void
    {
        ActivityLog::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'new_values' => [
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'assigned_to' => $task->assigned_to,
            ],
        ]);
    }

    public function updated(Task $task): void
    {
        $changes = $task->getDirty();
        $oldValues = [];
        $newValues = [];
        $actions = [];

        foreach ($changes as $key => $newValue) {
            $oldValue = $task->getOriginal($key);

            if ($key === 'status') {
                $actions[] = 'status_changed';
                $oldValues['status'] = $oldValue;
                $newValues['status'] = $newValue;
            } elseif ($key === 'assigned_to') {
                $actions[] = 'assigned';
                $oldValues['assigned_to'] = $oldValue;
                $newValues['assigned_to'] = $newValue;
            } elseif ($key === 'title') {
                $actions[] = 'title_updated';
                $oldValues['title'] = $oldValue;
                $newValues['title'] = $newValue;
            } elseif ($key === 'description') {
                $actions[] = 'description_updated';
                $oldValues['description'] = $oldValue;
                $newValues['description'] = $newValue;
            }
        }

        if (empty($actions)) {
            return;
        }

        foreach ($actions as $action) {
            ActivityLog::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'action' => $action,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        }
    }

    public function deleting(Task $task): void
    {
        ActivityLog::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'old_values' => [
                'title' => $task->title,
                'status' => $task->status,
            ],
        ]);
    }
}
