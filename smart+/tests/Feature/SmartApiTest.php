<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;

class SmartApiTest extends TestCase
{
    use RefreshDatabase;

    private string $validToken = 'smart_secret_token_almadinah_2026';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $tahun = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => now()->startOfYear(),
            'tanggal_selesai' => now()->endOfYear(),
            'is_aktif' => true,
        ]);

        Siswa::create([
            'user_id' => $user->id,
            'nisn' => '0012345678',
            'nama_lengkap' => 'Ahmad Test',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $tahun->id,
        ]);
    }

    public function test_smart_health_check_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'smart-plus',
                'version' => 'v1',
            ])
            ->assertJsonStructure(['status', 'service', 'version', 'timestamp']);
    }

    public function test_smart_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/students');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'request_id',
                ]
            ]);
    }

    public function test_smart_authenticated_get_students_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->validToken)
            ->getJson('/api/v1/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'external_id',
                        'nisn',
                        'nama',
                        'kelas',
                    ]
                ],
                'meta' => ['page', 'per_page', 'total', 'has_more', 'request_id']
            ]);
    }

    public function test_smart_authenticated_get_classes_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->validToken)
            ->getJson('/api/v1/classes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nama_kelas',
                        'tingkat',
                        'total_siswa',
                    ]
                ],
                'meta' => ['total', 'request_id']
            ]);
    }

    public function test_smart_authenticated_get_attendance_records_returns_canonical_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->validToken)
            ->getJson('/api/v1/attendance/records?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'student_external_id',
                        'student_name',
                        'class_name',
                        'attendance_date',
                        'status',
                        'is_final',
                    ]
                ],
                'meta' => ['page', 'per_page', 'total', 'has_more', 'request_id']
            ]);
    }

    public function test_smart_authenticated_get_attendance_summaries_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->validToken)
            ->getJson('/api/v1/attendance/summaries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'student_id',
                        'student_external_id',
                        'student_name',
                        'class_name',
                        'counts' => [
                            'present',
                            'late',
                            'sick',
                            'permission',
                            'unexcused_absence',
                            'total_recorded_days',
                        ],
                        'report_card_fields' => [
                            'sakit',
                            'izin',
                            'tanpa_keterangan',
                        ],
                        'attendance_percentage',
                    ]
                ],
                'meta' => ['total', 'request_id']
            ]);
    }
}
