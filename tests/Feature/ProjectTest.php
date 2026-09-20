<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_lists_projects_and_supports_search(): void
    {
        Project::factory()->create(['name' => 'Alpha Board']);
        Project::factory()->create(['name' => 'Beta Board']);

        $this->actingAs($this->user)
            ->get('/projects')
            ->assertOk()
            ->assertSee('Alpha Board')
            ->assertSee('Beta Board');

        $this->actingAs($this->user)
            ->get('/projects?q=Alpha')
            ->assertOk()
            ->assertSee('Alpha Board')
            ->assertDontSee('Beta Board');
    }

    public function test_index_filters_by_status(): void
    {
        Project::factory()->create(['name' => 'Live One', 'status' => Project::STATUS_ACTIVE]);
        Project::factory()->create(['name' => 'Old One', 'status' => Project::STATUS_ARCHIVED]);

        $this->actingAs($this->user)
            ->get('/projects?status=archived')
            ->assertOk()
            ->assertSee('Old One')
            ->assertDontSee('Live One');
    }

    public function test_project_crud(): void
    {
        $this->actingAs($this->user)
            ->post('/projects', [
                'name' => 'New Project',
                'description' => 'A fresh board.',
                'status' => Project::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['name' => 'New Project']);

        $project = Project::query()->where('name', 'New Project')->firstOrFail();

        $this->actingAs($this->user)
            ->put("/projects/{$project->id}", [
                'name' => 'Renamed Project',
                'description' => 'Edited.',
                'status' => Project::STATUS_ARCHIVED,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Renamed Project', 'status' => Project::STATUS_ARCHIVED]);

        $this->actingAs($this->user)
            ->delete("/projects/{$project->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_validation_rejects_invalid_input(): void
    {
        $this->actingAs($this->user)
            ->post('/projects', [
                'name' => '',
                'status' => 'not-a-status',
            ])
            ->assertSessionHasErrors(['name', 'status']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_project_show_returns_404_for_missing(): void
    {
        $this->actingAs($this->user)->get('/projects/99999')->assertNotFound();
    }

    public function test_guest_cannot_access_project_resources(): void
    {
        $this->get('/projects')->assertRedirect('/login');
        $this->post('/projects', ['name' => 'X'])->assertRedirect('/login');
    }
}
