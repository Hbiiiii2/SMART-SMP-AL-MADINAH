<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RaporSetting extends Model
{
    use HasFactory;

    protected $table = 'rapor_settings';

    protected $fillable = [
        'nama_kurikulum',
        'tipe_judul_rapor',
        'judul_rapor_sts',
        'judul_rapor_akhir',
        'show_kurikulum',
        'show_fase',
        'fase_text',
        'ukuran_kertas',
        'show_ranking',
        'show_kkm',
        'default_kkm',
        'kota_pembagian',
        'tanggal_rapor',
        'nama_kepala_sekolah',
        'nip_kepala_sekolah',
        'ttd_kepala_sekolah',
        'stempel_sekolah',
        'header_yayasan',
        'header_sekolah',
    ];

    protected $casts = [
        'show_kurikulum' => 'boolean',
        'show_fase' => 'boolean',
        'show_ranking' => 'boolean',
        'show_kkm' => 'boolean',
        'default_kkm' => 'float',
        'tanggal_rapor' => 'date',
    ];

    /**
     * Ensure new columns exist on rapor_settings table (auto-heal for Docker containers).
     */
    protected static function ensureColumnsExist(): void
    {
        try {
            if (Schema::hasTable('rapor_settings')) {
                $missingPaper = !Schema::hasColumn('rapor_settings', 'ukuran_kertas');
                $missingRanking = !Schema::hasColumn('rapor_settings', 'show_ranking');
                $missingShowKkm = !Schema::hasColumn('rapor_settings', 'show_kkm');
                $missingDefaultKkm = !Schema::hasColumn('rapor_settings', 'default_kkm');
                $missingTipeJudul = !Schema::hasColumn('rapor_settings', 'tipe_judul_rapor');
                $missingJudulSts = !Schema::hasColumn('rapor_settings', 'judul_rapor_sts');
                $missingJudulAkhir = !Schema::hasColumn('rapor_settings', 'judul_rapor_akhir');

                if ($missingPaper || $missingRanking || $missingShowKkm || $missingDefaultKkm || $missingTipeJudul || $missingJudulSts || $missingJudulAkhir) {
                    Schema::table('rapor_settings', function (Blueprint $table) use ($missingPaper, $missingRanking, $missingShowKkm, $missingDefaultKkm, $missingTipeJudul, $missingJudulSts, $missingJudulAkhir) {
                        if ($missingPaper) {
                            $table->string('ukuran_kertas', 20)->default('A4')->after('show_fase');
                        }
                        if ($missingRanking) {
                            $table->boolean('show_ranking')->default(true);
                        }
                        if ($missingShowKkm) {
                            $table->boolean('show_kkm')->default(false);
                        }
                        if ($missingDefaultKkm) {
                            $table->decimal('default_kkm', 5, 2)->default(75.00);
                        }
                        if ($missingTipeJudul) {
                            $table->string('tipe_judul_rapor', 20)->default('template')->after('nama_kurikulum');
                        }
                        if ($missingJudulSts) {
                            $table->string('judul_rapor_sts', 191)->nullable()->after('tipe_judul_rapor');
                        }
                        if ($missingJudulAkhir) {
                            $table->string('judul_rapor_akhir', 191)->nullable()->after('judul_rapor_sts');
                        }
                    });
                }
            }

            if (Schema::hasTable('mata_pelajaran')) {
                if (!Schema::hasColumn('mata_pelajaran', 'kkm')) {
                    Schema::table('mata_pelajaran', function (Blueprint $table) {
                        $table->decimal('kkm', 5, 2)->nullable()->default(75.00)->after('nama');
                    });
                }
            }
        } catch (\Throwable $e) {
            // Ignore schema inspection errors
        }
    }

    /**
     * Get or create singleton instance of RaporSetting.
     */
    public static function instance(): self
    {
        self::ensureColumnsExist();

        return self::firstOrCreate(
            ['id' => 1],
            [
                'nama_kurikulum' => 'Kurikulum Merdeka',
                'show_kurikulum' => true,
                'show_fase' => false, // Default is false per user requirement: "gak ada fase/d dll"
                'fase_text' => 'Fase D',
                'ukuran_kertas' => 'A4',
                'show_ranking' => true,
                'kota_pembagian' => 'Bogor',
                'header_yayasan' => 'YAYASAN KELUARGA BESAR MASYARAKAT BANTEN (KKMB)',
                'header_sekolah' => 'SMP AL - MADINAH ISLAMIC CENTER',
            ]
        );
    }

    /**
     * Resolve effective report title based on template vs custom setting.
     */
    public function getEffectiveJudulRapor(string $jenisRapor = 'sts'): string
    {
        if ($this->tipe_judul_rapor === 'custom') {
            if ($jenisRapor === 'sts' && !empty(trim($this->judul_rapor_sts ?? ''))) {
                return trim($this->judul_rapor_sts);
            }
            if ($jenisRapor !== 'sts' && !empty(trim($this->judul_rapor_akhir ?? ''))) {
                return trim($this->judul_rapor_akhir);
            }
        }

        return ($jenisRapor === 'sts')
            ? 'LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER (STS)'
            : 'LAPORAN CAPAIAN HASIL BELAJAR PESERTA DIDIK';
    }
}
