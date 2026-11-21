<?php

namespace App\Services\Integration;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MasterDataSyncService
{
    /**
     * Sync classes and students from SMART+ Core system via API.
     *
     * @return array{classes_synced: int, students_synced: int, errors: array<string>}
     */
    public function syncFromSmartPlus(?string $baseUrl = null, ?string $token = null): array
    {
        $baseUrl = rtrim($baseUrl ?: config('services.smart_plus.base_url', 'http://127.0.0.1:8000/api/v1'), '/');
        $token = $token ?: config('services.smart_plus.token', env('SMART_PLUS_API_TOKEN'));

        $stats = [
            'classes_synced' => 0,
            'students_synced' => 0,
            'errors' => [],
        ];

        // 1. Sync Classes
        try {
            $classResponse = Http::timeout(10)
                ->withToken($token)
                ->get("{$baseUrl}/classes");

            if ($classResponse->successful()) {
                $classesData = $classResponse->json('data') ?? [];
                foreach ($classesData as $item) {
                    $namaKelas = trim($item['nama_kelas'] ?? $item['kelas'] ?? '');
                    if (! $namaKelas) {
                        continue;
                    }

                    $tingkat = $item['tingkat'] ?? null;
                    if (! $tingkat) {
                        preg_match('/(VII|VIII|IX|\d+)/i', $namaKelas, $matches);
                        $tingkat = $matches[0] ?? 'VII';
                    }

                    Kelas::updateOrCreate(
                        ['nama_kelas' => $namaKelas],
                        [
                            'tingkat' => $tingkat,
                            'status' => 'aktif',
                        ]
                    );
                    $stats['classes_synced']++;
                }
            } else {
                $stats['errors'][] = 'Gagal mengambil data kelas dari SMART+: HTTP ' . $classResponse->status();
            }
        } catch (Throwable $e) {
            Log::warning('MasterDataSyncService: Kelas sync failed: ' . $e->getMessage());
            $stats['errors'][] = 'Kelas sync exception: ' . $e->getMessage();
        }

        // Cache kelas mapping (nama_kelas => id)
        $kelasMap = Kelas::pluck('id', 'nama_kelas')->all();

        // 2. Sync Students with Pagination
        try {
            $page = 1;
            $hasMore = true;

            while ($hasMore && $page <= 50) {
                $studentResponse = Http::timeout(15)
                    ->withToken($token)
                    ->get("{$baseUrl}/students", [
                        'page' => $page,
                        'per_page' => 100,
                    ]);

                if (! $studentResponse->successful()) {
                    $stats['errors'][] = "Gagal mengambil data siswa halaman {$page}: HTTP " . $studentResponse->status();
                    break;
                }

                $studentsData = $studentResponse->json('data') ?? [];
                $hasMore = (bool) ($studentResponse->json('meta.has_more') ?? false);

                if (empty($studentsData)) {
                    break;
                }

                DB::transaction(function () use ($studentsData, $kelasMap, &$stats): void {
                    foreach ($studentsData as $s) {
                        $nisn = trim((string) ($s['nisn'] ?? ''));
                        $nama = trim((string) ($s['nama'] ?? ''));
                        $kelasNama = trim((string) ($s['kelas'] ?? ''));
                        $nis = trim((string) ($s['nis'] ?? $nisn));
                        $tahunAjaran = $s['tahun_ajaran']['nama'] ?? null;

                        if (! $nisn || ! $nama) {
                            continue;
                        }

                        $kelasId = $kelasMap[$kelasNama] ?? null;

                        // Ensure User exists for Student Login
                        $username = $nisn;
                        $user = User::firstOrCreate(
                            ['username' => $username],
                            [
                                'name' => $nama,
                                'email' => "{$username}@siswa.smp-almadinah.sch.id",
                                'role' => 'siswa',
                                'password' => Hash::make($nisn),
                            ]
                        );

                        Siswa::updateOrCreate(
                            ['nisn' => $nisn],
                            [
                                'user_id' => $user->id,
                                'nis' => $nis,
                                'nama' => $nama,
                                'kelas' => $kelasNama,
                                'kelas_id' => $kelasId,
                                'tahun_ajaran' => $tahunAjaran,
                                'status_aktif' => true,
                            ]
                        );

                        $stats['students_synced']++;
                    }
                });

                $page++;
            }
        } catch (Throwable $e) {
            Log::warning('MasterDataSyncService: Siswa sync failed: ' . $e->getMessage());
            $stats['errors'][] = 'Siswa sync exception: ' . $e->getMessage();
        }

        return $stats;
    }
}
