<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/tasks')->assertRedirect('/login');
        $this->get('/projects')->assertRedirect('/login');
    }

    public function test_user_can_register_and_session_starts(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'new@example.com',
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'secret-123']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        User::factory()->create(['password' => 'secret-123']);

        $this->post('/login', [
            'email' => 'never@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_register_with_duplicate_email_fails(): void
    {
        $existing = User::factory()->create();

        $this->post('/register', [
            'name' => 'Another',
            'email' => $existing->email,
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
        ])->assertSessionHasErrors('email');
    }
}
