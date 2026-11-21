<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JadwalRequest;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Services\CacheService;
use App\Traits\LogsActivity;

class JadwalController extends Controller
{
    use LogsActivity;
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Jadwal::with('guru', 'mataPelajaran', 'lokasi', 'tahunAjaran')->latest();

        // Default ke tahun ajaran aktif jika tidak ada filter
        $tahunAjaranAktif = TahunAjaran::where('is_aktif', true)->first();
        $tahunAjaranFilter = $request->tahun_ajaran_id ?? ($tahunAjaranAktif ? $tahunAjaranAktif->id : null);

        if ($tahunAjaranFilter) {
            $query->where('tahun_ajaran_id', $tahunAjaranFilter);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kelas', 'like', "%{$search}%")
                  ->orWhere('hari', 'like', "%{$search}%")
                  ->orWhereHas('guru', function ($q2) use ($search) {
                      $q2->where('nama_lengkap', 'like', "%{$search}%")
                         ->orWhere('nip', 'like', "%{$search}%");
                  })
                  ->orWhereHas('mataPelajaran', function ($q2) use ($search) {
                      $q2->where('nama', 'like', "%{$search}%");
                  })
                  ->orWhereHas('lokasi', function ($q2) use ($search) {
                      $q2->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('hari')) {
            $query->where('hari', $request->hari);
        }

        if ($request->filled('guru_id')) {
            $query->where('guru_id', $request->guru_id);
        }

        if ($request->filled('mata_pelajaran_id')) {
            $query->where('mata_pelajaran_id', $request->mata_pelajaran_id);
        }

        $jadwals = $query->paginate(15)->appends($request->all());
        
        $gurus = Guru::orderBy('nama_lengkap')->get();
        $mataPelajarans = MataPelajaran::orderBy('nama')->get();
        $hariOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $tahunAjarans = TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();

        return view('pages.admin.jadwal.index', [
            'title' => 'Jadwal Pelajaran', 
            'jadwals' => $jadwals,
            'gurus' => $gurus,
            'mataPelajarans' => $mataPelajarans,
            'hariOptions' => $hariOptions,
            'tahunAjarans' => $tahunAjarans,
            'tahunAjaranFilter' => $tahunAjaranFilter,
        ]);
    }

    public function create()
    {
        $kelasOptions = \App\Models\Siswa::distinct()->pluck('kelas')
            ->merge(\App\Models\Jadwal::distinct()->pluck('kelas'))
            ->filter()
            ->unique()
            ->values()
            ->sort();

        return view('pages.admin.jadwal.create', [
            'title' => 'Tambah Jadwal',
            'gurus' => Guru::with('user')->get(),
            'mataPelajarans' => MataPelajaran::all(),
            'tahunAjarans' => TahunAjaran::orderByDesc('is_aktif')->get(),
            'lokasis' => Lokasi::all(),
            'kelasOptions' => $kelasOptions,
        ]);
    }

    public function store(JadwalRequest $request)
    {
        CacheService::flushDropdowns();

        Jadwal::create($request->validated());

        $this->logActivity('create_jadwal');

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function edit(Jadwal $jadwal)
    {
        $kelasOptions = \App\Models\Siswa::distinct()->pluck('kelas')
            ->merge(\App\Models\Jadwal::distinct()->pluck('kelas'))
            ->filter()
            ->unique()
            ->values()
            ->sort();

        return view('pages.admin.jadwal.edit', [
            'title' => 'Edit Jadwal',
            'jadwal' => $jadwal,
            'gurus' => Guru::with('user')->get(),
            'mataPelajarans' => MataPelajaran::all(),
            'tahunAjarans' => TahunAjaran::orderByDesc('is_aktif')->get(),
            'lokasis' => Lokasi::all(),
            'kelasOptions' => $kelasOptions,
        ]);
    }

    public function update(JadwalRequest $request, Jadwal $jadwal)
    {
        CacheService::flushDropdowns();

        $jadwal->update($request->validated());

        $this->logActivity('update_jadwal');

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal pelajaran berhasil diperbarui.');
    }

    public function destroy(Jadwal $jadwal)
    {
        CacheService::flushDropdowns();

        $jadwal->delete();

        $this->logActivity('delete_jadwal');

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }
}
