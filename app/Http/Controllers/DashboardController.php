<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;

class DashboardController extends Controller
{
    public function show()
    {
        return view('dashboard', [
            'projectCount' => Project::count(),
            'taskCount' => Task::count(),
            'todoCount' => Task::where('status', Task::STATUS_TODO)->count(),
            'inProgressCount' => Task::where('status', Task::STATUS_IN_PROGRESS)->count(),
            'doneCount' => Task::where('status', Task::STATUS_DONE)->count(),
        ]);
    }
}
