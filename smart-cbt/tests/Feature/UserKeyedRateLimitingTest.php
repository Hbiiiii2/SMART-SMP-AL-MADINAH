<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Examination\ExamAttemptService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class UserKeyedRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private function createSetup(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $guru = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru Rate', 'kode_guru' => 'G-RT-01']);
        $kelas = Kelas::create(['nama_kelas' => 'VIII-RATE', 'tingkat' => 'VIII', 'status' => 'aktif']);
        $mapel = MataPelajaran::create(['kode' => 'MAPEL-RT', 'nama' => 'Rate Mapel']);

        $exam = Exam::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'nama' => 'Rate Limit Test Exam',
            'kode_ujian' => 'RT-' . uniqid(),
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinute(),
            'tanggal_selesai' => now()->addHour(),
            'token' => 'RAT123',
            'token_aktif' => true,
        ]);

        $q = Question::create([
            'exam_id' => $exam->id,
            'tipe' => 'pg',
            'pertanyaan' => 'Sample Rate Question',
            'kunci' => 'A',
            'bobot' => 1,
            'urutan' => 1,
        ]);

        // Student 1
        $u1 = User::factory()->create(['role' => 'siswa']);
        $s1 = Siswa::create([
            'user_id' => $u1->id,
            'nis' => 'STU-01',
            'nisn' => 'STU-01',
            'nama' => 'Student One',
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'status_aktif' => true,
        ]);

        // Student 2
        $u2 = User::factory()->create(['role' => 'siswa']);
        $s2 = Siswa::create([
            'user_id' => $u2->id,
            'nis' => 'STU-02',
            'nisn' => 'STU-02',
            'nama' => 'Student Two',
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'status_aktif' => true,
        ]);

        return [$exam, $q, $u1, $s1, $u2, $s2];
    }

    /**
     * BLK-03: Two students sharing the exact same NAT IP do NOT exhaust each other's rate limit quota.
     */
    public function test_two_students_on_same_nat_ip_have_independent_rate_limits(): void
    {
        [$exam, $q, $u1, $s1, $u2, $s2] = $this->createSetup();

        $attemptService = app(ExamAttemptService::class);
        $att1 = $attemptService->startOrResumeAttempt($exam, $s1, 'RAT123');
        $att2 = $attemptService->startOrResumeAttempt($exam, $s2, 'RAT123');

        $sharedNatIp = '180.252.12.34'; // Simulated shared school public IP

        // Configure test rate limit: 2 requests per minute per user
        RateLimiter::for('exam-autosave', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(2)->by($key);
        });

        // Student 1: Request 1 (allowed)
        $res1 = $this->actingAs($u1)
            ->withSession(["siswa.exam_token_verified.{$exam->id}" => $att1->id])
            ->withServerVariables(['REMOTE_ADDR' => $sharedNatIp])
            ->postJson(route('siswa.ujian.answers', $exam), ['answers' => [$q->id => 'A'], 'client_seqs' => [$q->id => 1]]);
        $res1->assertStatus(200);

        // Student 1: Request 2 (allowed)
        $res2 = $this->actingAs($u1)
            ->withSession(["siswa.exam_token_verified.{$exam->id}" => $att1->id])
            ->withServerVariables(['REMOTE_ADDR' => $sharedNatIp])
            ->postJson(route('siswa.ujian.answers', $exam), ['answers' => [$q->id => 'A'], 'client_seqs' => [$q->id => 2]]);
        $res2->assertStatus(200);

        // Student 1: Request 3 (exceeded -> 429)
        $res3 = $this->actingAs($u1)
            ->withSession(["siswa.exam_token_verified.{$exam->id}" => $att1->id])
            ->withServerVariables(['REMOTE_ADDR' => $sharedNatIp])
            ->postJson(route('siswa.ujian.answers', $exam), ['answers' => [$q->id => 'A'], 'client_seqs' => [$q->id => 3]]);
        $res3->assertStatus(429);

        // Student 2 from the EXACT SAME NAT IP must still have full quota!
        $resStudent2 = $this->actingAs($u2)
            ->withSession(["siswa.exam_token_verified.{$exam->id}" => $att2->id])
            ->withServerVariables(['REMOTE_ADDR' => $sharedNatIp])
            ->postJson(route('siswa.ujian.answers', $exam), ['answers' => [$q->id => 'B'], 'client_seqs' => [$q->id => 1]]);
        $resStudent2->assertStatus(200);
        $resStudent2->assertJson(['saved' => true, 'count' => 1]);
    }

    /**
     * BLK-03: Exam start token submission rate limiting is isolated per user on the same IP.
     */
    public function test_exam_start_rate_limiter_isolated_per_user_on_same_ip(): void
    {
        [$exam, $q, $u1, $s1, $u2, $s2] = $this->createSetup();

        $sharedNatIp = '192.168.1.1';

        // Configure test rate limit: 2 requests per minute per user
        RateLimiter::for('exam-start', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(2)->by($key);
        });

        // Student 1: Request 1
        $r1 = $this->actingAs($u1)
            ->withServerVariables(['REMOTE_ADDR' => $sharedNatIp])
            ->post(route('siswa.ujian.start', $exam), ['token' => 'RAT123']);
        $r1->assertRedirect(route('siswa.ujian.work', $exam));

        // Student 1: Request 2
        $r2 = $this->actingAs($u1)
            ->withServerVariables(['REMOTE_ADDR' => $sharedNatIp])
            ->post(route('siswa.ujian.start', $exam), ['token' => 'RAT123']);
        $r2->assertRedirect(route('siswa.ujian.work', $exam));

        // Student 1: Request 3 -> hits 429
        $r3 = $this->actingAs($u1)
            ->withServerVariables(['REMOTE_ADDR' => $sharedNatIp])
            ->post(route('siswa.ujian.start', $exam), ['token' => 'RAT123']);
        $r3->assertStatus(429);

        // Student 2 from same IP is NOT rate limited and can start exam
        $rStudent2 = $this->actingAs($u2)
            ->withServerVariables(['REMOTE_ADDR' => $sharedNatIp])
            ->post(route('siswa.ujian.start', $exam), ['token' => 'RAT123']);
        $rStudent2->assertRedirect(route('siswa.ujian.work', $exam));
    }
}
