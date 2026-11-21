<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('SMART');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_sekolah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_guru_redirected_to_guru_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        \App\Models\Guru::create([
            'user_id' => $user->id,
            'nip' => '1234567890',
            'nama_lengkap' => $user->name,
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->actingAs($user)->get('/guru/dashboard');
        $response->assertStatus(200);
    }

    public function test_role_middleware_blocks_unauthorized(): void
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        $response = $this->actingAs($guru)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_security_headers_present(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }
}
