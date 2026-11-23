<?php

use App\Models\Answer;
use App\Models\AnswerAttachment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Examination\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function setupEssayPhotoExam(): array
{
    $guruUser = User::factory()->create(['role' => 'guru']);
    $guru = Guru::create([
        'user_id' => $guruUser->id,
        'nama' => 'Guru Matematika',
        'kode_guru' => 'GM-' . fake()->unique()->numerify('###'),
    ]);

    $otherGuruUser = User::factory()->create(['role' => 'guru']);
    Guru::create([
        'user_id' => $otherGuruUser->id,
        'nama' => 'Guru Lain',
        'kode_guru' => 'GL-' . fake()->unique()->numerify('###'),
    ]);

    $adminUser = User::factory()->create(['role' => 'admin']);

    $kelas = Kelas::create([
        'nama_kelas' => 'IX-A',
        'tingkat' => '9',
        'status' => 'aktif',
    ]);

    $mapel = MataPelajaran::create([
        'kode' => 'MTK-' . fake()->unique()->numerify('###'),
        'nama' => 'Matematika',
    ]);

    $exam = Exam::create([
        'guru_id' => $guru->id,
        'mata_pelajaran_id' => $mapel->id,
        'nama' => 'UAS Matematika',
        'kode_ujian' => 'MTK-' . fake()->unique()->numerify('####'),
        'kelas_id' => $kelas->id,
        'kelas' => $kelas->nama_kelas,
        'durasi_menit' => 90,
        'tanggal_mulai' => now()->subMinutes(10),
        'tanggal_selesai' => now()->addHours(2),
        'status' => 'aktif',
        'token' => 'MTK999',
        'token_aktif' => true,
        'komponen_soal' => ['pg', 'essay_1', 'essay_2'],
    ]);

    $questionImagePath = UploadedFile::fake()->image('soal_essay2.png', 400, 300)->store('questions', 'public');

    $qPg = Question::create([
        'exam_id' => $exam->id,
        'tipe' => 'pg',
        'pertanyaan' => '2 + 2 = ?',
        'opsi_a' => '3',
        'opsi_b' => '4',
        'opsi_c' => '5',
        'opsi_d' => '6',
        'kunci' => 'B',
        'bobot' => 2,
        'urutan' => 1,
    ]);

    $qEssay1 = Question::create([
        'exam_id' => $exam->id,
        'tipe' => 'essay_1',
        'pertanyaan' => 'Selesaikan persamaan kuadrat x^2 - 5x + 6 = 0!',
        'bobot' => 4,
        'urutan' => 2,
    ]);

    $qEssay2 = Question::create([
        'exam_id' => $exam->id,
        'tipe' => 'essay_2',
        'pertanyaan' => 'Hitung luas bangun pada gambar berikut!',
        'image' => $questionImagePath,
        'bobot' => 4,
        'urutan' => 3,
    ]);

    $siswaAUser = User::factory()->create(['role' => 'siswa']);
    $siswaA = Siswa::create([
        'user_id' => $siswaAUser->id,
        'nis' => '10001',
        'nisn' => '0010001',
        'nama' => 'Siswa Satu',
        'kelas_id' => $kelas->id,
        'kelas' => $kelas->nama_kelas,
        'status_aktif' => true,
    ]);

    $siswaBUser = User::factory()->create(['role' => 'siswa']);
    $siswaB = Siswa::create([
        'user_id' => $siswaBUser->id,
        'nis' => '10002',
        'nisn' => '0010002',
        'nama' => 'Siswa Dua',
        'kelas_id' => $kelas->id,
        'kelas' => $kelas->nama_kelas,
        'status_aktif' => true,
    ]);

    $attemptService = app(ExamAttemptService::class);
    $attemptA = $attemptService->startOrResumeAttempt($exam, $siswaA, 'MTK999');
    $attemptB = $attemptService->startOrResumeAttempt($exam, $siswaB, 'MTK999');

    return [
        'guruUser' => $guruUser,
        'otherGuruUser' => $otherGuruUser,
        'adminUser' => $adminUser,
        'exam' => $exam,
        'qPg' => $qPg,
        'qEssay1' => $qEssay1,
        'qEssay2' => $qEssay2,
        'siswaAUser' => $siswaAUser,
        'siswaBUser' => $siswaBUser,
        'attemptA' => $attemptA,
        'attemptB' => $attemptB,
    ];
}

test('student can upload, replace, delete, and view essay photo answers for essay_1 and essay_2', function () {
    Storage::fake('public');
    Storage::fake('exam_private');

    $ctx = setupEssayPhotoExam();
    $exam = $ctx['exam'];
    $sessionKey = "siswa.exam_token_verified.{$exam->id}";

    // 1. Work page renders teacher question image and dual-mode essay controls
    $workRes = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->get(route('siswa.ujian.work', $exam));

    $workRes->assertOk()
        ->assertSee('Essay 1')
        ->assertSee('Essay 2')
        ->assertSee('Jawaban Teks')
        ->assertSee('Jawaban Foto')
        ->assertSee('embedded-camera-modal')
        ->assertSee('openEmbeddedCamera')
        ->assertSee(Storage::url($ctx['qEssay2']->image));

    // 2. Upload JPEG to essay_1
    $upload1 = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay1']]), [
            'photo' => UploadedFile::fake()->image('coretan1.jpg', 800, 600),
        ]);

    $upload1->assertCreated()
        ->assertJsonPath('persisted', true)
        ->assertJsonPath('answer_type', 'photo')
        ->assertJsonPath('total_attachments', 1);

    $att1Id = $upload1->json('attachment.id');
    $att1 = AnswerAttachment::findOrFail($att1Id);
    Storage::disk('exam_private')->assertExists($att1->file_path);

    // 3. Upload PNG & WebP to essay_2
    $upload2 = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay2']]), [
            'photo' => UploadedFile::fake()->image('diagram.png', 600, 400),
        ]);
    $upload2->assertCreated();

    $upload3 = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay2']]), [
            'photo' => UploadedFile::fake()->image('langkah2.webp', 600, 400),
        ]);
    $upload3->assertCreated()
        ->assertJsonPath('total_attachments', 2);

    // 4. Replace photo on essay_1
    $oldPath = $att1->file_path;
    $replaceRes = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay1']]), [
            'photo' => UploadedFile::fake()->image('revisi.png', 700, 500),
            'replace_attachment_id' => $att1Id,
        ]);

    $replaceRes->assertOk()
        ->assertJsonPath('persisted', true)
        ->assertJsonPath('attachment.id', $att1Id);

    $att1->refresh();
    Storage::disk('exam_private')->assertMissing($oldPath);
    Storage::disk('exam_private')->assertExists($att1->file_path);

    // 5. Delete 1 photo from essay_2
    $att3Id = $upload3->json('attachment.id');
    $att3Path = AnswerAttachment::findOrFail($att3Id)->file_path;

    $deleteRes = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->deleteJson(route('siswa.ujian.lampiran.destroy', [$exam, $att3Id]));

    $deleteRes->assertOk()
        ->assertJsonPath('deleted', true)
        ->assertJsonPath('remaining_count', 1);

    expect(AnswerAttachment::find($att3Id))->toBeNull();
    Storage::disk('exam_private')->assertMissing($att3Path);
});

test('invalid files, pg uploads, and exceeding max photo count are rejected with 422', function () {
    Storage::fake('public');
    Storage::fake('exam_private');

    $ctx = setupEssayPhotoExam();
    $exam = $ctx['exam'];
    $sessionKey = "siswa.exam_token_verified.{$exam->id}";

    // Reject SVG / PDF
    $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay1']]), [
            'photo' => UploadedFile::fake()->create('xss.svg', 10, 'image/svg+xml'),
        ])
        ->assertStatus(422);

    $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay1']]), [
            'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(422);

    // Reject PG question
    $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qPg']]), [
            'photo' => UploadedFile::fake()->image('pg.jpg', 200, 200),
        ])
        ->assertStatus(422);

    // Reject 4th upload
    for ($i = 1; $i <= 3; $i++) {
        $this->actingAs($ctx['siswaAUser'])
            ->withSession([$sessionKey => $ctx['attemptA']->id])
            ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay1']]), [
                'photo' => UploadedFile::fake()->image("p{$i}.jpg", 200, 200),
            ])
            ->assertCreated();
    }

    $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay1']]), [
            'photo' => UploadedFile::fake()->image('p4.jpg', 200, 200),
        ])
        ->assertStatus(422);
});

test('private attachment access is strictly scoped and post-submit mutations are blocked', function () {
    Storage::fake('public');
    Storage::fake('exam_private');

    $ctx = setupEssayPhotoExam();
    $exam = $ctx['exam'];
    $sessionKey = "siswa.exam_token_verified.{$exam->id}";

    $uploadRes = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay1']]), [
            'photo' => UploadedFile::fake()->image('jawaban_rahasia.jpg', 400, 300),
        ])
        ->assertCreated();

    $attachmentId = $uploadRes->json('attachment.id');
    $attachmentUrl = route('lampiran-jawaban.show', $attachmentId);

    // Clear authentication from previous actingAs() before checking guest redirect
    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    $this->flushSession();

    // Guest redirected to login
    $this->get($attachmentUrl)->assertRedirect(route('login'));

    // Student B forbidden
    $this->actingAs($ctx['siswaBUser'])->get($attachmentUrl)->assertForbidden();

    // Student B cannot delete Student A attachment
    $this->actingAs($ctx['siswaBUser'])
        ->withSession([$sessionKey => $ctx['attemptB']->id])
        ->deleteJson(route('siswa.ujian.lampiran.destroy', [$exam, $attachmentId]))
        ->assertForbidden();

    // Unrelated Guru forbidden
    $this->actingAs($ctx['otherGuruUser'])->get($attachmentUrl)->assertForbidden();

    // Owner Student A, Exam Guru, and Admin allowed with nosniff header
    $this->actingAs($ctx['siswaAUser'])->get($attachmentUrl)
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->actingAs($ctx['guruUser'])->get($attachmentUrl)
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->actingAs($ctx['adminUser'])->get($attachmentUrl)->assertOk();

    // Submit exam -> post-submit upload & delete rejected (403)
    $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->post(route('siswa.ujian.submit', $exam), [
            'answers' => [
                $ctx['qPg']->id => 'B',
                $ctx['qEssay1']->id => '',
                $ctx['qEssay2']->id => '',
            ],
        ])
        ->assertRedirect(route('siswa.ujian.result', $exam));

    $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay2']]), [
            'photo' => UploadedFile::fake()->image('late.jpg', 200, 200),
        ])
        ->assertForbidden();

    $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->deleteJson(route('siswa.ujian.lampiran.destroy', [$exam, $attachmentId]))
        ->assertForbidden();
});

test('teacher can view photo-only essay_1 and essay_2 answers and grade them completely', function () {
    Storage::fake('public');
    Storage::fake('exam_private');

    $ctx = setupEssayPhotoExam();
    $exam = $ctx['exam'];
    $sessionKey = "siswa.exam_token_verified.{$exam->id}";

    // Upload photos for essay_1 and essay_2 (leaving text empty)
    $u1 = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay1']]), [
            'photo' => UploadedFile::fake()->image('e1.jpg', 500, 400),
        ])->assertCreated();

    $u2 = $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->postJson(route('siswa.ujian.lampiran.store', [$exam, $ctx['qEssay2']]), [
            'photo' => UploadedFile::fake()->image('e2.png', 500, 400),
        ])->assertCreated();

    // Submit exam
    $this->actingAs($ctx['siswaAUser'])
        ->withSession([$sessionKey => $ctx['attemptA']->id])
        ->post(route('siswa.ujian.submit', $exam), [
            'answers' => [
                $ctx['qPg']->id => 'B',
                $ctx['qEssay1']->id => '',
                $ctx['qEssay2']->id => '',
            ],
        ])
        ->assertRedirect(route('siswa.ujian.result', $exam));

    $attempt = $ctx['attemptA']->fresh();
    expect($attempt->status)->toBe('needs_grading');

    // Teacher views hasil.show -> sees photo URLs and "Belum Dinilai" (NOT "Tidak Dijawab")
    $hasilRes = $this->actingAs($ctx['guruUser'])
        ->get(route('guru.hasil.show', $attempt->id));

    $hasilRes->assertOk()
        ->assertSee('Essay 1')
        ->assertSee('Essay 2')
        ->assertSee('Belum Dinilai')
        ->assertDontSee('Tidak Dijawab')
        ->assertSee(route('lampiran-jawaban.show', $u1->json('attachment.id')))
        ->assertSee(route('lampiran-jawaban.show', $u2->json('attachment.id')));

    // Teacher grades essay_1 (3/4) and essay_2 (4/4) -> total (2 + 3 + 4) / 10 = 90
    $ans1 = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $ctx['qEssay1']->id)->firstOrFail();
    $ans2 = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $ctx['qEssay2']->id)->firstOrFail();

    $this->actingAs($ctx['guruUser'])
        ->post(route('guru.hasil.grade-essay', $attempt->id), [
            'answer_id' => $ans1->id,
            'skor' => 3,
            'catatan_guru' => 'Langkah penyelesaian tepat',
        ])
        ->assertSessionHas('success');

    $this->actingAs($ctx['guruUser'])
        ->post(route('guru.hasil.grade-essay', $attempt->id), [
            'answer_id' => $ans2->id,
            'skor' => 4,
            'catatan_guru' => 'Perhitungan sempurna',
        ])
        ->assertSessionHas('success');

    $attempt->refresh();
    expect($attempt->status)->toBe('completed')
        ->and((float) $attempt->nilai_pg)->toBe(2.0)
        ->and((float) $attempt->nilai_essay)->toBe(7.0)
        ->and((float) $attempt->nilai_akhir)->toBe(90.0);
});
