<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Tugas;
use App\Models\PengumpulanTugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TugasController extends Controller
{
    public function index()
    {
        $guruId = auth()->user()->guru->id;
        $tugas = Tugas::where('guru_id', $guruId)->with('mataPelajaran')->latest()->get();
        return view('pages.guru.tugas.index', compact('tugas'));
    }

    public function create()
    {
        $guruId = auth()->user()->guru->id;
        $jadwals = Jadwal::where('guru_id', $guruId)->with('mataPelajaran')->get();
        
        $kelasOptions = $jadwals->pluck('kelas')->unique();
        $mapelOptions = $jadwals->pluck('mataPelajaran')->unique('id');

        return view('pages.guru.tugas.create', compact('kelasOptions', 'mapelOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kelas' => 'required|string',
            'mata_pelajaran_id' => [
                'required',
                'exists:mata_pelajaran,id',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = \App\Models\Jadwal::where('guru_id', auth()->user()->guru->id)
                        ->where('kelas', $request->kelas)
                        ->where('mata_pelajaran_id', $value)
                        ->exists();

                    if (!$exists) {
                        $fail('Mata pelajaran ini tidak terdaftar untuk kelas yang dipilih pada jadwal Anda.');
                    }
                },
            ],
            'deskripsi' => 'required|string',
            'file_lampiran' => 'nullable|file|max:10240', // max 10MB
            'tenggat_waktu' => 'required|date',
            'tipe_pengumpulan' => 'required|array|min:1',
            'tipe_pengumpulan.*' => 'in:link_url,file'
        ]);

        $tugasData = $request->except(['file_lampiran', 'tipe_pengumpulan']);
        $tugasData['tipe_pengumpulan'] = implode(',', $request->tipe_pengumpulan);
        $tugas = new Tugas($tugasData);
        $tugas->guru_id = auth()->user()->guru->id;

        if ($request->hasFile('file_lampiran')) {
            $path = $request->file('file_lampiran')->store('tugas', 'public');
            $tugas->file_path = $path;
        }

        $tugas->save();

        return redirect()->route('guru.tugas.index')->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function show(Tugas $tugas)
    {
        if ($tugas->guru_id != auth()->user()->guru->id) {
            return response("<h1>Akses Ditolak (403)</h1><p>Anda mencoba mengakses Tugas ID: {$tugas->id} yang dibuat oleh Guru ID: {$tugas->guru_id}. Sedangkan Anda login sebagai Guru ID: " . auth()->user()->guru->id . ". Anda hanya bisa menilai tugas yang Anda buat sendiri.</p>", 403);
        }

        $pengumpulans = $tugas->pengumpulanTugas()->with('siswa')->get();
        return view('pages.guru.tugas.show', compact('tugas', 'pengumpulans'));
    }

    public function edit(Tugas $tugas)
    {
        if ($tugas->guru_id != auth()->user()->guru->id) {
            abort(403);
        }

        $jadwals = Jadwal::where('guru_id', auth()->user()->guru->id)->with('mataPelajaran')->get();
        $kelasOptions = $jadwals->pluck('kelas')->unique();
        $mapelOptions = $jadwals->pluck('mataPelajaran')->unique('id');

        return view('pages.guru.tugas.edit', compact('tugas', 'kelasOptions', 'mapelOptions'));
    }

    public function update(Request $request, Tugas $tugas)
    {
        if ($tugas->guru_id != auth()->user()->guru->id) {
            abort(403);
        }

        $request->validate([
            'judul' => 'required|string|max:255',
            'kelas' => 'required|string',
            'mata_pelajaran_id' => [
                'required',
                'exists:mata_pelajaran,id',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = \App\Models\Jadwal::where('guru_id', auth()->user()->guru->id)
                        ->where('kelas', $request->kelas)
                        ->where('mata_pelajaran_id', $value)
                        ->exists();

                    if (!$exists) {
                        $fail('Mata pelajaran ini tidak terdaftar untuk kelas yang dipilih pada jadwal Anda.');
                    }
                },
            ],
            'deskripsi' => 'required|string',
            'file_lampiran' => 'nullable|file|max:10240',
            'tenggat_waktu' => 'required|date',
            'tipe_pengumpulan' => 'required|array|min:1',
            'tipe_pengumpulan.*' => 'in:link_url,file'
        ]);

        $tugasData = $request->except(['file_lampiran', 'tipe_pengumpulan']);
        $tugasData['tipe_pengumpulan'] = implode(',', $request->tipe_pengumpulan);
        $tugas->fill($tugasData);

        if ($request->hasFile('file_lampiran')) {
            if ($tugas->file_path && Storage::disk('public')->exists($tugas->file_path)) {
                Storage::disk('public')->delete($tugas->file_path);
            }
            $path = $request->file('file_lampiran')->store('tugas', 'public');
            $tugas->file_path = $path;
        }

        $tugas->save();

        return redirect()->route('guru.tugas.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Tugas $tugas)
    {
        if ($tugas->guru_id != auth()->user()->guru->id) {
            abort(403);
        }

        if ($tugas->file_path && Storage::disk('public')->exists($tugas->file_path)) {
            Storage::disk('public')->delete($tugas->file_path);
        }

        $tugas->delete();

        return redirect()->route('guru.tugas.index')->with('success', 'Tugas berhasil dihapus.');
    }

    public function nilai(Request $request, Tugas $tugas, PengumpulanTugas $pengumpulan)
    {
        if ($tugas->guru_id != auth()->user()->guru->id || $pengumpulan->tugas_id != $tugas->id) {
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki izin untuk menilai tugas ini.');
        }

        $request->validate([
            'nilai' => 'required|numeric|min:0|max:100',
            'catatan_guru' => 'nullable|string'
        ]);

        $pengumpulan->nilai = $request->nilai;
        $pengumpulan->catatan_guru = $request->catatan_guru;
        $pengumpulan->status = 'dinilai';
        $pengumpulan->save();

        return back()->with('success', 'Nilai berhasil disimpan.');
    }

    public function export(Tugas $tugas)
    {
        if ($tugas->guru_id != auth()->user()->guru->id) {
            abort(403, 'Akses ditolak.');
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\NilaiTugasExport($tugas->id),
            'Nilai_Tugas_' . str_replace(' ', '_', $tugas->judul) . '_' . date('Y-m-d') . '.xlsx'
        );
    }
}
