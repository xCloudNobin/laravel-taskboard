<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_liveness_returns_ok(): void
    {
        $response = $this->get('/health/live');

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_readiness_returns_ready_when_database_available(): void
    {
        $this->get('/health/ready')
            ->assertOk()
            ->assertJson(['status' => 'ready', 'checks' => ['database' => 'up']]);
    }

    public function test_readiness_returns_503_when_database_unavailable(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->andThrow(new class extends \RuntimeException {});

        $this->get('/health/ready')
            ->assertStatus(503)
            ->assertJson(['status' => 'unavailable', 'checks' => ['database' => 'down']]);
    }

    public function test_version_exposes_non_sensitive_release_marker(): void
    {
        $response = $this->get('/version');

        $response->assertOk()
            ->assertJsonStructure([
                'app',
                'framework',
                'php',
                'release',
                'commit',
                'environment',
            ]);
    }

    public function test_root_redirects_to_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }
}
