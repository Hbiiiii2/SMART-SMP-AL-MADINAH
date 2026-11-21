<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Siswa;

class CbtApiTest extends TestCase
{
    use RefreshDatabase;
    private string $validToken = 'cbt_secret_token_almadinah_2026';

    public function test_health_check_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'smart-cbt',
                'version' => 'v1',
            ])
            ->assertJsonStructure(['status', 'service', 'version', 'timestamp']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/exams');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'request_id',
                ]
            ]);
    }

    public function test_authenticated_get_exams_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->validToken)
            ->getJson('/api/v1/exams');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'per_page', 'total', 'has_more', 'request_id']
            ]);
    }

    public function test_authenticated_get_exam_results_returns_conforming_contract(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->validToken)
            ->getJson('/api/v1/exam-results');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'attempt_external_id',
                        'exam_external_id',
                        'exam_name',
                        'student_external_id',
                        'student_name',
                        'subject_name',
                        'score',
                        'max_score',
                        'status',
                    ]
                ],
                'meta' => ['page', 'per_page', 'total', 'has_more', 'request_id']
            ]);
    }
}
