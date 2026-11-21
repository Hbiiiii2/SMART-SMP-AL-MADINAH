<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiswaRequest;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SiswaController extends Controller
{
    use LogsActivity;
    public function index(Request $request)
    {
        $siswas = Siswa::with('user', 'tahunAjaran')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($query) use ($s) {
                    $query->where('nama_lengkap', 'like', "%$s%")
                          ->orWhere('nisn', 'like', "%$s%");
                });
            })
            ->when($request->kelas, fn ($q, $k) => $q->where('kelas', $k))
            ->when($request->tahun_ajaran_id, fn ($q, $t) => $q->where('tahun_ajaran_id', $t))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $tahunAjarans = TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();
        $kelases = Siswa::whereNotNull('kelas')->where('kelas', '!=', '')->distinct()->pluck('kelas')->sort();

        return view('pages.admin.siswa.index', compact('siswas', 'tahunAjarans', 'kelases'));
    }

    public function create()
    {
        $tahunAjarans = TahunAjaran::where('is_aktif', true)->get();

        return view('pages.admin.siswa.create', ['title' => 'Tambah Siswa', 'tahunAjarans' => $tahunAjarans]);
    }

    public function store(SiswaRequest $request)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->nama_lengkap,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'siswa',
                'is_active' => true,
            ]);

            $data = $request->validated();
            if ($request->hasFile('foto')) {
                $data['foto'] = $request->file('foto')->store('foto/siswa', 'public');
            }
            unset($data['email'], $data['password']);

            $user->siswa()->create($data);
        });

        $this->logActivity('create_siswa', 'Menambah siswa: ' . $request->nama_lengkap, $request);

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Siswa $siswa)
    {
        $siswa->load('user', 'rfidCards', 'absensiSiswas.jadwal.mataPelajaran', 'tahunAjaran');

        return view('pages.admin.siswa.show', ['title' => 'Detail Siswa', 'siswa' => $siswa]);
    }

    public function edit(Siswa $siswa)
    {
        $siswa->load('user');
        $tahunAjarans = TahunAjaran::orderByDesc('is_aktif')->get();

        return view('pages.admin.siswa.edit', ['title' => 'Edit Siswa', 'siswa' => $siswa, 'tahunAjarans' => $tahunAjarans]);
    }

    public function update(SiswaRequest $request, Siswa $siswa)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($request, $siswa) {
            $siswa->user->update(['name' => $request->nama_lengkap, 'email' => $request->email]);

            $data = $request->validated();
            unset($data['email'], $data['password']);

            if ($request->hasFile('foto')) {
                if ($siswa->foto) {
                    Storage::disk('public')->delete($siswa->foto);
                }
                $data['foto'] = $request->file('foto')->store('foto/siswa', 'public');
            }

            $siswa->update($data);

            if ($request->filled('password')) {
                $siswa->user->update(['password' => Hash::make($request->password)]);
            }
        });

        $this->logActivity('update_siswa', 'Mengubah data siswa: ' . $siswa->nama_lengkap, $request);

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($siswa) {
            if ($siswa->foto) {
                Storage::disk('public')->delete($siswa->foto);
            }
            $siswa->user->delete();
            $siswa->delete();
        });

        $this->logActivity('delete_siswa', 'Menghapus siswa: ' . $siswa->nama_lengkap);

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    public function kenaikanKelas()
    {
        $tahunAjarans = TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();
        $kelases = Siswa::whereNotNull('kelas')->where('kelas', '!=', '')->distinct()->pluck('kelas')->sort();
        
        // Group all students by their current class
        $siswasByKelas = Siswa::with('tahunAjaran')->orderBy('nama_lengkap')->get()->groupBy('kelas');

        return view('pages.admin.siswa.kenaikan-kelas', compact('tahunAjarans', 'kelases', 'siswasByKelas'));
    }

    public function prosesKenaikanKelas(Request $request)
    {
        $request->validate([
            'tahun_ajaran_tujuan' => 'required|exists:tahun_ajaran,id',
            'kelas_tujuan' => 'nullable|array',
            'siswa_naik' => 'nullable|array',
        ]);

        $tahunAjaranTujuanId = $request->tahun_ajaran_tujuan;
        $kelasTujuanMap = $request->kelas_tujuan ?? [];
        $siswaNaikMap = $request->siswa_naik ?? [];

        DB::transaction(function () use ($tahunAjaranTujuanId, $kelasTujuanMap, $siswaNaikMap) {
            foreach ($kelasTujuanMap as $kelasAsal => $kelasTujuan) {
                if (empty($kelasTujuan)) {
                    continue; // Skip if no target class selected
                }

                // Get all students currently in this class
                $semuaSiswaDiKelas = Siswa::where('kelas', $kelasAsal)->get();
                $siswaNaikIds = $siswaNaikMap[$kelasAsal] ?? [];

                // Separate them into naik and tinggal
                $siswaNaik = $semuaSiswaDiKelas->whereIn('id', $siswaNaikIds);
                $siswaTinggal = $semuaSiswaDiKelas->whereNotIn('id', $siswaNaikIds);

                // Update siswa naik kelas / lulus
                if ($siswaNaik->isNotEmpty()) {
                    // Archive previous class and register target class
                    foreach ($siswaNaik as $sn) {
                        \App\Models\RiwayatKelasSiswa::updateOrCreate(
                            [
                                'siswa_id' => $sn->id,
                                'tahun_ajaran_id' => $sn->tahun_ajaran_id ?? $tahunAjaranTujuanId,
                                'semester' => 'Genap',
                            ],
                            [
                                'kelas' => $kelasAsal,
                                'status' => (strtolower(trim($kelasTujuan)) === 'alumni' || strtolower(trim($kelasTujuan)) === 'lulus') ? 'lulus' : 'naik_kelas',
                                'catatan' => "Naik ke {$kelasTujuan}",
                            ]
                        );

                        \App\Models\RiwayatKelasSiswa::updateOrCreate(
                            [
                                'siswa_id' => $sn->id,
                                'tahun_ajaran_id' => $tahunAjaranTujuanId,
                                'semester' => 'Ganjil',
                            ],
                            [
                                'kelas' => $kelasTujuan,
                                'status' => (strtolower(trim($kelasTujuan)) === 'alumni' || strtolower(trim($kelasTujuan)) === 'lulus') ? 'alumni' : 'aktif',
                                'catatan' => "Tahun Ajaran Baru di {$kelasTujuan}",
                            ]
                        );
                    }

                    Siswa::whereIn('id', $siswaNaik->pluck('id'))->update([
                        'kelas' => $kelasTujuan,
                        'tahun_ajaran_id' => $tahunAjaranTujuanId,
                    ]);

                    // Jika kelas tujuan adalah Alumni / Lulus, nonaktifkan user-nya
                    if (strtolower(trim($kelasTujuan)) === 'alumni' || strtolower(trim($kelasTujuan)) === 'lulus') {
                        $userIds = $siswaNaik->pluck('user_id')->filter()->toArray();
                        if (!empty($userIds)) {
                            \App\Models\User::whereIn('id', $userIds)->update(['is_active' => false]);
                        }
                    }
                }

                // Update siswa tinggal kelas (kelas tetap, tapi tahun ajaran maju)
                if ($siswaTinggal->isNotEmpty()) {
                    foreach ($siswaTinggal as $st) {
                        \App\Models\RiwayatKelasSiswa::updateOrCreate(
                            [
                                'siswa_id' => $st->id,
                                'tahun_ajaran_id' => $tahunAjaranTujuanId,
                                'semester' => 'Ganjil',
                            ],
                            [
                                'kelas' => $kelasAsal,
                                'status' => 'tinggal_kelas',
                                'catatan' => "Tinggal di kelas {$kelasAsal}",
                            ]
                        );
                    }

                    Siswa::whereIn('id', $siswaTinggal->pluck('id'))->update([
                        'tahun_ajaran_id' => $tahunAjaranTujuanId,
                    ]);
                }
            }
        });

        CacheService::flushDropdowns();
        $this->logActivity('kenaikan_kelas_massal', 'Memproses kenaikan kelas massal');

        return redirect()->route('admin.siswa.index')->with('success', 'Kenaikan kelas massal berhasil diproses.');
    }
}
