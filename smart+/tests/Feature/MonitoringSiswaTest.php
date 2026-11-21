<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class MonitoringSiswaTest extends TestCase
{
    public function test_monitoring_siswa_page_loads()
    {
        $user = User::where('role', 'kepala_sekolah')->first();
        
        $response = $this->actingAs($user)
                         ->get('/kepala-sekolah/monitoring-siswa');

        $response->assertStatus(200);
    }
}
