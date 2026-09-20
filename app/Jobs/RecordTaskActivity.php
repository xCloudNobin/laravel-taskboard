<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordTaskActivity implements ShouldQueue
{
    use Queueable;

    public const ACTION_CREATED = 'task.created';

    public const ACTION_UPDATED = 'task.updated';

    public const ACTION_DELETED = 'task.deleted';

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly int $projectId,
        public readonly int $taskId,
        public readonly string $action,
        public readonly array $context = [],
    ) {}

    /**
     * Persist a database-backed activity log entry for the task change.
     */
    public function handle(): void
    {
        ActivityLog::create([
            'project_id' => $this->projectId,
            'task_id' => $this->taskId,
            'action' => $this->action,
            'message' => match ($this->action) {
                self::ACTION_CREATED => 'Task created',
                self::ACTION_UPDATED => 'Task updated',
                self::ACTION_DELETED => 'Task deleted',
                default => 'Task changed',
            },
            'context' => $this->context,
        ]);
    }
}
