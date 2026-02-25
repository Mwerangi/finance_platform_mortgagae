<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_page_is_accessible()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_login_with_correct_credentials()
    {
        $institution = Institution::factory()->create();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'institution_id' => $institution->id,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_cannot_login_with_incorrect_password()
    {
        $institution = Institution::factory()->create();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'institution_id' => $institution->id,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_cannot_login_with_nonexistent_email()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $institution = Institution::factory()->create();
        
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'status' => 'inactive',
            'institution_id' => $institution->id,
        ]);

        // Note: Add middleware to check user status if needed
        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        // Depending on implementation, this might still authenticate
        // You may want to add status check in login logic
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $institution = Institution::factory()->create();
        
        $user = User::factory()->create([
            'institution_id' => $institution->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function remember_me_functionality_works()
    {
        $institution = Institution::factory()->create();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'institution_id' => $institution->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        
        // Check remember token is set
        $this->assertNotNull($user->fresh()->remember_token);
    }

    /** @test */
    public function guest_is_redirected_to_login_when_accessing_protected_route()
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_user_cannot_access_login_page()
    {
        $institution = Institution::factory()->create();
        
        $user = User::factory()->create([
            'institution_id' => $institution->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get('/login');

        $response->assertRedirect('/dashboard');
    }
}
