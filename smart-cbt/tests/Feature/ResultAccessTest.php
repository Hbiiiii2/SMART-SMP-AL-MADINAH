<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createExamWithAttempt(User $guruUser, string $examName, string $studentName): ExamAttempt
{
    $guru = Guru::create([
        'user_id' => $guruUser->id,
        'nama' => $guruUser->name,
        'kode_guru' => fake()->unique()->bothify('GURU-###'),
    ]);
    $subject = MataPelajaran::create(['kode' => fake()->unique()->bothify('MAP-###'), 'nama' => fake()->unique()->word()]);
    $class = Kelas::create(['nama_kelas' => fake()->unique()->bothify('X-#'), 'tingkat' => '10', 'status' => 'aktif']);
    $exam = Exam::create([
        'guru_id' => $guru->id,
        'mata_pelajaran_id' => $subject->id,
        'nama' => $examName,
        'kelas_id' => $class->id,
        'kelas' => $class->nama_kelas,
        'ruangan' => 'Lab 1',
        'kode_ujian' => fake()->unique()->bothify('EXAM-####'),
        'durasi_menit' => 60,
        'status' => 'aktif',
        'komponen_soal' => ['pg'],
    ]);
    $studentUser = User::factory()->create(['name' => $studentName, 'role' => 'siswa']);
    $student = Siswa::create(['user_id' => $studentUser->id, 'nis' => fake()->unique()->numerify('########'), 'nama' => $studentName, 'kelas' => $class->nama_kelas]);

    return ExamAttempt::create(['exam_id' => $exam->id, 'siswa_id' => $student->id, 'status' => 'submitted', 'nilai_akhir' => 90]);
}

test('guru only sees results for assigned exams', function () {
    $guru = User::factory()->create(['role' => 'guru', 'name' => 'Guru Satu']);
    $otherGuru = User::factory()->create(['role' => 'guru', 'name' => 'Guru Dua']);
    $ownedAttempt = createExamWithAttempt($guru, 'Ujian Guru Satu', 'Siswa Satu');
    $otherAttempt = createExamWithAttempt($otherGuru, 'Ujian Guru Dua', 'Siswa Dua');

    $this->actingAs($guru)
        ->get(route('guru.hasil.index'))
        ->assertOk()
        ->assertSee($ownedAttempt->exam->nama)
        ->assertDontSee($otherAttempt->exam->nama)
        ->assertSee('Siswa Satu')
        ->assertDontSee('Siswa Dua');
});

test('student cannot access teacher result listing', function () {
    $student = User::factory()->create(['role' => 'siswa']);

    $this->actingAs($student)
        ->get(route('guru.hasil.index'))
        ->assertForbidden();
});

test('student completion page and exam lists hide final score', function () {
    $guruUser = User::factory()->create(['role' => 'guru', 'name' => 'Guru Bahasa']);
    $guru = Guru::create([
        'user_id' => $guruUser->id,
        'nama' => $guruUser->name,
        'kode_guru' => fake()->unique()->bothify('GURU-###'),
    ]);
    $subject = MataPelajaran::create(['kode' => fake()->unique()->bothify('MAP-###'), 'nama' => 'Bahasa Indonesia']);
    $class = Kelas::create(['nama_kelas' => fake()->unique()->bothify('IX-#'), 'tingkat' => '9', 'status' => 'aktif']);
    $exam = Exam::create([
        'guru_id' => $guru->id,
        'mata_pelajaran_id' => $subject->id,
        'nama' => 'STS Bahasa Indonesia IX',
        'kelas_id' => $class->id,
        'kelas' => $class->nama_kelas,
        'ruangan' => 'Lab 1',
        'kode_ujian' => fake()->unique()->bothify('EXAM-####'),
        'durasi_menit' => 60,
        'status' => 'aktif',
        'token' => 'CBT999',
        'token_aktif' => true,
        'komponen_soal' => ['pg'],
    ]);
    $studentUser = User::factory()->create(['name' => 'Siswa Uji', 'role' => 'siswa']);
    $student = Siswa::create([
        'user_id' => $studentUser->id,
        'nis' => fake()->unique()->numerify('########'),
        'nama' => 'Siswa Uji',
        'kelas_id' => $class->id,
        'kelas' => $class->nama_kelas,
        'status_aktif' => true,
    ]);

    ExamAttempt::create([
        'exam_id' => $exam->id,
        'siswa_id' => $student->id,
        'started_at' => now()->subMinutes(45),
        'submitted_at' => now()->subMinutes(5),
        'status' => 'submitted',
        'nilai_akhir' => 93.75,
    ]);

    $this->actingAs($studentUser)
        ->get(route('siswa.ujian.result', $exam))
        ->assertOk()
        ->assertSee('Ujian Selesai')
        ->assertSee('Siswa Uji')
        ->assertSee($student->nis)
        ->assertSee($class->nama_kelas)
        ->assertSee('Terkumpul')
        ->assertDontSee('93.75');

    $this->actingAs($studentUser)
        ->get(route('siswa.ujian.index'))
        ->assertOk()
        ->assertSee($exam->nama)
        ->assertDontSee('93.75');

    $this->actingAs($studentUser)
        ->get(route('siswa.hasil'))
        ->assertOk()
        ->assertSee($exam->nama)
        ->assertSee('Terkumpul')
        ->assertDontSee('93.75');
});