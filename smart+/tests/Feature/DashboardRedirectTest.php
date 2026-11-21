<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_dashboard_redirects_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_accessing_dashboard_redirects_to_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_guru_accessing_dashboard_redirects_to_guru_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('guru.dashboard'));
    }

    public function test_kepala_sekolah_accessing_dashboard_redirects_to_ks_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'kepala_sekolah']);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('ks.dashboard'));
    }

    public function test_siswa_accessing_dashboard_redirects_to_siswa_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('siswa.dashboard'));
    }

    public function test_staff_akademik_accessing_dashboard_redirects_to_staff_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'staff_akademik']);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('staff.dashboard'));
    }

    public function test_direktur_accessing_dashboard_redirects_to_direktur_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'direktur']);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('direktur.dashboard'));
    }
}
