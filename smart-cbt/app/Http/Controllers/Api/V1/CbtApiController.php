<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CbtApiController extends Controller
{
    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'smart-cbt',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * List exams with filters and pagination
     */
    public function getExams(Request $request): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $query = Exam::with(['guru:id,nama', 'mataPelajaran:id,nama', 'kelasData:id,nama_kelas'])
            ->withCount(['questions', 'examAttempts']);

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        if ($request->filled('subject_id')) {
            $query->where('mata_pelajaran_id', $request->integer('subject_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('kelas_id', $request->integer('class_id'));
        }

        if ($request->filled('tahun_ajaran')) {
            $query->where('tahun_ajaran', $request->string('tahun_ajaran'));
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->string('semester'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        $items = collect($paginator->items())->map(function (Exam $exam) {
            return [
                'id' => $exam->id,
                'external_id' => (string) $exam->id,
                'kode_ujian' => $exam->kode_ujian,
                'nama' => $exam->nama,
                'jenis' => $exam->jenis,
                'tahun_ajaran' => $exam->tahun_ajaran,
                'semester' => $exam->semester,
                'durasi_menit' => $exam->durasi_menit,
                'status' => $exam->status,
                'total_soal' => (int) ($exam->questions_count ?? 0),
                'total_peserta' => (int) ($exam->exam_attempts_count ?? 0),
                'mata_pelajaran' => [
                    'id' => $exam->mata_pelajaran_id,
                    'nama' => $exam->mataPelajaran?->nama,
                ],
                'kelas' => [
                    'id' => $exam->kelas_id,
                    'nama_kelas' => $exam->kelasData?->nama_kelas ?? $exam->kelas,
                ],
                'guru' => [
                    'id' => $exam->guru_id,
                    'nama' => $exam->guru?->nama,
                ],
                'tanggal_mulai' => $exam->tanggal_mulai?->toIso8601String(),
                'tanggal_selesai' => $exam->tanggal_selesai?->toIso8601String(),
                'updated_at' => $exam->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Single exam detail
     */
    public function getExamDetail($id): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $exam = Exam::with(['guru:id,nama', 'mataPelajaran:id,nama', 'kelasData:id,nama_kelas'])
            ->find($id);

        if (!$exam) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Ujian tidak ditemukan.',
                    'details' => ['exam_id' => $id],
                    'request_id' => $requestId,
                ]
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $exam->id,
                'external_id' => (string) $exam->id,
                'kode_ujian' => $exam->kode_ujian,
                'nama' => $exam->nama,
                'jenis' => $exam->jenis,
                'tahun_ajaran' => $exam->tahun_ajaran,
                'semester' => $exam->semester,
                'durasi_menit' => $exam->durasi_menit,
                'status' => $exam->status,
                'total_soal' => (int) ($exam->questions_count ?? 0),
                'total_peserta' => (int) ($exam->exam_attempts_count ?? 0),
                'mata_pelajaran' => [
                    'id' => $exam->mata_pelajaran_id,
                    'nama' => $exam->mataPelajaran?->nama,
                ],
                'kelas' => [
                    'id' => $exam->kelas_id,
                    'nama_kelas' => $exam->kelasData?->nama_kelas ?? $exam->kelas,
                ],
                'total_soal' => $exam->questions()->count(),
                'tanggal_mulai' => $exam->tanggal_mulai?->toIso8601String(),
                'tanggal_selesai' => $exam->tanggal_selesai?->toIso8601String(),
                'updated_at' => $exam->updated_at?->toIso8601String(),
            ],
            'meta' => [
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * List exam attempts
     */
    public function getExamAttempts(Request $request): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $query = ExamAttempt::with(['exam:id,nama,kode_ujian,mata_pelajaran_id', 'siswa:id,nama,nis,nisn,kelas_id']);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->integer('exam_id'));
        }

        if ($request->filled('student_id')) {
            $studentSearch = $request->string('student_id');
            $query->whereHas('siswa', function ($q) use ($studentSearch) {
                $q->where('id', $studentSearch)
                  ->orWhere('nisn', $studentSearch)
                  ->orWhere('nis', $studentSearch);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        $items = collect($paginator->items())->map(function (ExamAttempt $attempt) {
            return [
                'id' => $attempt->id,
                'attempt_external_id' => (string) $attempt->id,
                'exam_external_id' => (string) $attempt->exam_id,
                'exam_name' => $attempt->exam?->nama,
                'student_external_id' => (string) $attempt->siswa_id,
                'student_nisn' => $attempt->siswa?->nisn,
                'student_nis' => $attempt->siswa?->nis,
                'student_name' => $attempt->siswa?->nama,
                'status' => $attempt->status,
                'attempt_number' => $attempt->attempt_number,
                'score' => $attempt->nilai_akhir !== null ? (float) $attempt->nilai_akhir : null,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'updated_at' => $attempt->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Single attempt detail
     */
    public function getExamAttemptDetail($id): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $attempt = ExamAttempt::with(['exam.mataPelajaran', 'exam.kelasData', 'siswa.kelasData'])
            ->find($id);

        if (!$attempt) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Attempt ujian tidak ditemukan.',
                    'details' => ['attempt_id' => $id],
                    'request_id' => $requestId,
                ]
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $attempt->id,
                'attempt_external_id' => (string) $attempt->id,
                'exam_external_id' => (string) $attempt->exam_id,
                'exam_name' => $attempt->exam?->nama,
                'exam_code' => $attempt->exam?->kode_ujian,
                'student_external_id' => (string) $attempt->siswa_id,
                'student_nisn' => $attempt->siswa?->nisn,
                'student_nis' => $attempt->siswa?->nis,
                'student_name' => $attempt->siswa?->nama,
                'class_name' => $attempt->siswa?->kelasData?->nama_kelas ?? $attempt->siswa?->kelas,
                'subject_name' => $attempt->exam?->mataPelajaran?->nama,
                'status' => $attempt->status,
                'attempt_number' => $attempt->attempt_number,
                'score' => $attempt->nilai_akhir !== null ? (float) $attempt->nilai_akhir : null,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'updated_at' => $attempt->updated_at?->toIso8601String(),
            ],
            'meta' => [
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * List finalized exam results eligible for E-Rapor gradebook integration (conforming to PRD 10.3)
     */
    public function getExamResults(Request $request): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $query = ExamAttempt::with(['exam.mataPelajaran', 'exam.kelasData', 'siswa.kelasData']);

        // Default: submitted, completed, and expired attempts (with scores) are eligible for gradebook
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } else {
            $query->whereIn('status', ['submitted', 'completed', 'expired']);
        }

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->integer('exam_id'));
        }

        if ($request->filled('student_id')) {
            $studentSearch = $request->string('student_id');
            $query->whereHas('siswa', function ($q) use ($studentSearch) {
                $q->where('id', $studentSearch)
                  ->orWhere('nisn', $studentSearch)
                  ->orWhere('nis', $studentSearch);
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('exam', fn ($q) => $q->where('kelas_id', $request->integer('class_id')));
        }

        if ($request->filled('tahun_ajaran')) {
            $query->whereHas('exam', fn ($q) => $q->where('tahun_ajaran', $request->string('tahun_ajaran')));
        }

        if ($request->filled('semester')) {
            $query->whereHas('exam', fn ($q) => $q->where('semester', $request->string('semester')));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        $perPage = min((int) $request->get('per_page', 100), 500);
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        $items = collect($paginator->items())->map(function (ExamAttempt $attempt) {
            $exam = $attempt->exam;
            $siswa = $attempt->siswa;

            // Canonical result status for E-Rapor
            $canonicalStatus = match ($attempt->status) {
                'submitted', 'completed', 'expired' => 'finalized',
                'in_progress' => 'in_progress',
                default => 'cancelled'
            };

            return [
                'id' => 'result_' . $attempt->id,
                'attempt_external_id' => (string) $attempt->id,
                'exam_external_id' => (string) $exam?->id,
                'exam_code' => $exam?->kode_ujian,
                'exam_name' => $exam?->nama,
                'exam_type' => $exam?->jenis,
                'student_external_id' => (string) $siswa?->id,
                'student_nisn' => $siswa?->nisn,
                'student_nis' => $siswa?->nis,
                'student_name' => $siswa?->nama,
                'class_external_id' => (string) ($siswa?->kelas_id ?? $exam?->kelas_id),
                'class_name' => $siswa?->kelasData?->nama_kelas ?? $exam?->kelasData?->nama_kelas ?? $siswa?->kelas,
                'subject_external_id' => (string) $exam?->mata_pelajaran_id,
                'subject_name' => $exam?->mataPelajaran?->nama,
                'academic_period_external_id' => trim(($exam?->tahun_ajaran ?? '') . '-' . ($exam?->semester ?? ''), '-'),
                'score' => $attempt->nilai_akhir !== null ? (float) $attempt->nilai_akhir : 0.0,
                'max_score' => 100.0,
                'status' => $canonicalStatus,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'updated_at' => $attempt->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Single exam result detail
     */
    public function getExamResultDetail($id): JsonResponse
    {
        $numericId = (int) str_replace('result_', '', $id);
        $attempt = ExamAttempt::with(['exam.mataPelajaran', 'exam.kelasData', 'siswa.kelasData'])
            ->find($numericId);

        $requestId = 'req_' . Str::uuid();

        if (!$attempt) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Hasil ujian tidak ditemukan.',
                    'details' => ['result_id' => $id],
                    'request_id' => $requestId,
                ]
            ], 404);
        }

        $exam = $attempt->exam;
        $siswa = $attempt->siswa;

        $canonicalStatus = match ($attempt->status) {
            'submitted', 'completed' => 'finalized',
            'in_progress' => 'in_progress',
            'expired' => 'expired',
            default => 'cancelled'
        };

        return response()->json([
            'data' => [
                'id' => 'result_' . $attempt->id,
                'attempt_external_id' => (string) $attempt->id,
                'exam_external_id' => (string) $exam?->id,
                'exam_code' => $exam?->kode_ujian,
                'exam_name' => $exam?->nama,
                'exam_type' => $exam?->jenis,
                'student_external_id' => (string) $siswa?->id,
                'student_nisn' => $siswa?->nisn,
                'student_nis' => $siswa?->nis,
                'student_name' => $siswa?->nama,
                'class_external_id' => (string) ($siswa?->kelas_id ?? $exam?->kelas_id),
                'class_name' => $siswa?->kelasData?->nama_kelas ?? $exam?->kelasData?->nama_kelas ?? $siswa?->kelas,
                'subject_external_id' => (string) $exam?->mata_pelajaran_id,
                'subject_name' => $exam?->mataPelajaran?->nama,
                'academic_period_external_id' => trim(($exam?->tahun_ajaran ?? '') . '-' . ($exam?->semester ?? ''), '-'),
                'score' => $attempt->nilai_akhir !== null ? (float) $attempt->nilai_akhir : 0.0,
                'max_score' => 100.0,
                'status' => $canonicalStatus,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'updated_at' => $attempt->updated_at?->toIso8601String(),
            ],
            'meta' => [
                'request_id' => $requestId,
            ],
        ]);
    }
}
