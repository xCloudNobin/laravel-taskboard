<?php

namespace Tests\Feature;

use App\Jobs\RecordTaskActivity;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    public function test_index_lists_tasks_and_supports_search_and_filters(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Pay the Rent', 'status' => Task::STATUS_DONE]);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Walk the Dog', 'status' => Task::STATUS_TODO]);

        $this->actingAs($this->user)
            ->get('/tasks')
            ->assertOk()
            ->assertSee('Pay the Rent')
            ->assertSee('Walk the Dog');

        $this->actingAs($this->user)
            ->get('/tasks?q=Rent')
            ->assertOk()
            ->assertSee('Pay the Rent')
            ->assertDontSee('Walk the Dog');

        $this->actingAs($this->user)
            ->get('/tasks?status=done')
            ->assertOk()
            ->assertSee('Pay the Rent')
            ->assertDontSee('Walk the Dog');

        $this->actingAs($this->user)
            ->get('/tasks?project_id='.$this->project->id)
            ->assertOk()
            ->assertSee('Pay the Rent');
    }

    public function test_task_crud_and_background_job_dispatched(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->post('/tasks', [
                'project_id' => $this->project->id,
                'title' => 'New Task',
                'description' => 'Do the thing.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_HIGH,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', ['title' => 'New Task']);

        Queue::assertPushed(RecordTaskActivity::class, function (RecordTaskActivity $job) {
            return $job->action === RecordTaskActivity::ACTION_CREATED
                && $job->projectId === $this->project->id
                && $job->context['title'] === 'New Task';
        });

        $task = Task::query()->where('title', 'New Task')->firstOrFail();

        $this->actingAs($this->user)
            ->put("/tasks/{$task->id}", [
                'project_id' => $this->project->id,
                'title' => 'Edited Task',
                'description' => 'Updated.',
                'status' => Task::STATUS_DONE,
                'priority' => Task::PRIORITY_LOW,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Edited Task', 'status' => Task::STATUS_DONE]);

        Queue::assertPushed(RecordTaskActivity::class, function (RecordTaskActivity $job) {
            return $job->action === RecordTaskActivity::ACTION_UPDATED;
        });

        $this->actingAs($this->user)
            ->delete("/tasks/{$task->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_validation_rejects_invalid_input(): void
    {
        $this->actingAs($this->user)
            ->post('/tasks', [
                'project_id' => $this->project->id,
                'title' => '',
                'status' => 'garbage',
                'priority' => 'highest',
            ])
            ->assertSessionHasErrors(['title', 'status', 'priority']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_create_rejects_unknown_project(): void
    {
        $this->actingAs($this->user)
            ->post('/tasks', [
                'project_id' => 99999,
                'title' => 'No Such Project',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_MEDIUM,
            ])
            ->assertSessionHasErrors('project_id');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_show_returns_404_for_missing(): void
    {
        $this->actingAs($this->user)->get('/tasks/99999')->assertNotFound();
    }

    public function test_background_job_persists_activity_log_row(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        RecordTaskActivity::dispatch(
            $task->project_id,
            $task->id,
            RecordTaskActivity::ACTION_CREATED,
            ['title' => $task->title, 'status' => $task->status, 'priority' => $task->priority],
        );

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $this->project->id,
            'task_id' => $task->id,
            'action' => RecordTaskActivity::ACTION_CREATED,
        ]);

        $log = ActivityLog::query()->where('task_id', $task->id)->firstOrFail();
        $this->assertSame($task->title, $log->context['title']);
    }
}
