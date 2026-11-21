<?php

namespace App\Services;

use App\Models\Siswa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    /**
     * Generate an AI-powered homeroom teacher note for report card.
     */
    public function generateCatatanWaliKelas(Siswa $siswa, ?float $avgScore, ?int $rank, array $attendance = []): string
    {
        $apiKey = config('services.openrouter.api_key');
        $baseUrl = rtrim(config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
        $model = config('services.openrouter.model', 'google/gemini-2.5-flash');

        $sakit = $attendance['s'] ?? 0;
        $izin = $attendance['i'] ?? 0;
        $alpha = $attendance['a'] ?? 0;

        // Default rule-based fallback
        $fallback = $this->getRuleBasedFallback($avgScore, $alpha);

        if (empty($apiKey)) {
            return $fallback;
        }

        $systemPrompt = "Kamu adalah asisten resmi Wali Kelas SMP Al Madinah (sekolah Islam berkualitas dengan Kurikulum Merdeka). Tugasmu adalah menyusun CATATAN WALI KELAS resmi yang akan dicetak pada lembar rapor siswa.
Pedoman penulisan:
1. Tulis 1 sampai 2 kalimat singkat, bernada santun, bijaksana, bernafaskan motivasi islami/akhlak mulia, dan konstruktif.
2. Panjang maksimal 220 karakter agar muat di kotak rapor resmi A4.
3. Selaraskan dengan capaian nilai rata-rata, peringkat, dan catatan presensi (sakit/izin/alpha).
4. Berikan langsung teks catatan rapor, TANPA tanda kutip dan TANPA kalimat pembuka.";

        $userPrompt = "Data Siswa:
Nama: {$siswa->nama_lengkap}
Kelas: {$siswa->kelas}
Rata-rata Nilai: " . ($avgScore !== null ? number_format($avgScore, 1) : 'Belum ada nilai') . "
Peringkat Kelas: " . ($rank ? "Peringkat ke-{$rank}" : 'Belum dihitung') . "
Kehadiran: Sakit {$sakit} hari, Izin {$izin} hari, Alpha/Tanpa Keterangan {$alpha} hari.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => config('app.url', 'http://localhost:8000'),
                'X-Title' => 'SMP Al Madinah SMART+',
                'Content-Type' => 'application/json',
            ])->timeout(12)->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'max_tokens' => 120,
                'temperature' => 0.7,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if ($response->successful()) {
                $content = trim($response->json('choices.0.message.content', ''));
                // Remove surrounding quotes if model added any
                $content = trim($content, " \t\n\r\0\x0B\"'");
                if (!empty($content)) {
                    return $content;
                }
            } else {
                Log::warning('OpenRouter API returned non-200: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('OpenRouter API request failed: ' . $e->getMessage());
        }

        return $fallback;
    }

    /**
     * Fallback template rule-based logic when AI is disabled or unreachable.
     */
    public function getRuleBasedFallback(?float $avgScore, int $alpha = 0): string
    {
        if ($avgScore && $avgScore >= 85) {
            $note = "Prestasi belajar sangat memuaskan dan patut diapresiasi. Pertahankan semangat belajar, ketekunan, dan akhlak mulia dalam setiap kegiatan.";
        } elseif ($avgScore && $avgScore >= 75) {
            $note = "Pencapaian belajar sudah baik. Tingkatkan terus kedisiplinan dan konsistensi dalam menyelesaikan tugas-tugas akademik serta aktif di kelas.";
        } else {
            $note = "Tingkatkan motivasi dan waktu belajar di rumah. Jangan ragu berdiskusi dengan guru pengampu mata pelajaran jika menemui kesulitan materi.";
        }

        if ($alpha >= 3) {
            $note .= " Mohon lebih memperhatikan kehadiran di sekolah dan kurangi ketidakhadiran tanpa keterangan.";
        }

        return $note;
    }
}
