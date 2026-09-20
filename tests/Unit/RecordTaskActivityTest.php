<?php

namespace Tests\Unit;

use App\Jobs\RecordTaskActivity;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecordTaskActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_writes_activity_log_row(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $job = new RecordTaskActivity(
            $project->id,
            $task->id,
            RecordTaskActivity::ACTION_UPDATED,
            ['title' => $task->title, 'status' => $task->status, 'priority' => $task->priority],
        );

        $job->handle();

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'task_id' => $task->id,
            'action' => RecordTaskActivity::ACTION_UPDATED,
            'message' => 'Task updated',
        ]);

        $log = ActivityLog::query()->where('task_id', $task->id)->firstOrFail();
        $this->assertSame($task->title, $log->context['title']);
    }

    public function test_job_queues_through_database_connection(): void
    {
        config()->set('queue.default', 'database');

        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        RecordTaskActivity::dispatch(
            $project->id,
            $task->id,
            RecordTaskActivity::ACTION_CREATED,
            ['title' => $task->title, 'status' => $task->status, 'priority' => $task->priority],
        );

        $this->assertSame(1, DB::table('jobs')->count());
    }
}
