<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with repeatable demo data.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('demo-password'),
            ],
        );

        $this->command?->info("Created demo user: {$user->email} / demo-password");

        $projects = [
            'Website Redesign' => 'Refresh the public marketing site with a modern design.',
            'Mobile App' => 'Fleet of tasks for the iOS and Android companion apps.',
            'Internal Tooling' => 'Small utilities that keep the team productive.',
        ];

        $titlesByProject = [
            'Website Redesign' => [
                'Audit current pages' => 'in_progress',
                'Design new component library' => 'todo',
                'Migrate content to new templates' => 'todo',
            ],
            'Mobile App' => [
                'Stand up CI for Android builds' => 'done',
                'Review push notification flow' => 'in_progress',
            ],
            'Internal Tooling' => [
                'Document the deploy process' => 'todo',
            ],
        ];

        foreach ($projects as $name => $description) {
            $project = Project::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'status' => Project::STATUS_ACTIVE],
            );

            foreach ($titlesByProject[$name] ?? [] as $title => $status) {
                Task::query()->updateOrCreate(
                    ['project_id' => $project->id, 'title' => $title],
                    [
                        'description' => 'Seeded demonstration task for '.$project->name.'.',
                        'status' => $status,
                        'priority' => Task::PRIORITY_MEDIUM,
                    ],
                );
            }
        }

        $this->command?->info('Seeded projects and tasks.');
    }
}
