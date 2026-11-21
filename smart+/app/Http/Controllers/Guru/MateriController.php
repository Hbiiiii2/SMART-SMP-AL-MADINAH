<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MateriController extends Controller
{
    public function index()
    {
        $guruId = auth()->user()->guru->id;
        $materi = Materi::where('guru_id', $guruId)->with('mataPelajaran')->latest()->get();
        return view('pages.guru.materi.index', compact('materi'));
    }

    public function create()
    {
        $guruId = auth()->user()->guru->id;
        // Get unique kelas and mata_pelajaran for this guru from jadwal
        $jadwals = Jadwal::where('guru_id', $guruId)->with('mataPelajaran')->get();
        
        $kelasOptions = $jadwals->pluck('kelas')->unique();
        $mapelOptions = $jadwals->pluck('mataPelajaran')->unique('id');

        return view('pages.guru.materi.create', compact('kelasOptions', 'mapelOptions'));
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
            'deskripsi' => 'nullable|string',
            'tipe_materi' => 'required|in:pdf,youtube,lainnya',
            'file_materi' => 'nullable|file|mimes:pdf|max:10240', // max 10MB
            'url_materi' => 'nullable|url'
        ]);

        $materi = new Materi($request->except(['file_materi', 'url_materi']));
        $materi->guru_id = auth()->user()->guru->id;

        if ($request->tipe_materi === 'pdf' && $request->hasFile('file_materi')) {
            $path = $request->file('file_materi')->store('materi', 'public');
            $materi->file_path_atau_url = $path;
        } elseif ($request->tipe_materi === 'youtube' && $request->filled('url_materi')) {
            $materi->file_path_atau_url = $request->url_materi;
        } elseif ($request->tipe_materi === 'lainnya' && $request->filled('url_materi')) {
            $materi->file_path_atau_url = $request->url_materi;
        }

        $materi->save();

        return redirect()->route('guru.materi.index')->with('success', 'Materi berhasil ditambahkan.');
    }

    public function show(Materi $materi)
    {
        if ($materi->guru_id !== auth()->user()->guru->id) {
            abort(403);
        }
        return view('pages.guru.materi.show', compact('materi'));
    }

    public function edit(Materi $materi)
    {
        if ($materi->guru_id !== auth()->user()->guru->id) {
            abort(403);
        }

        $jadwals = Jadwal::where('guru_id', auth()->user()->guru->id)->with('mataPelajaran')->get();
        $kelasOptions = $jadwals->pluck('kelas')->unique();
        $mapelOptions = $jadwals->pluck('mataPelajaran')->unique('id');

        return view('pages.guru.materi.edit', compact('materi', 'kelasOptions', 'mapelOptions'));
    }

    public function update(Request $request, Materi $materi)
    {
        if ($materi->guru_id !== auth()->user()->guru->id) {
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
            'deskripsi' => 'nullable|string',
            'tipe_materi' => 'required|in:pdf,youtube,lainnya',
            'file_materi' => 'nullable|file|mimes:pdf|max:10240',
            'url_materi' => 'nullable|url'
        ]);

        $materi->fill($request->except(['file_materi', 'url_materi']));

        if ($request->tipe_materi === 'pdf' && $request->hasFile('file_materi')) {
            if ($materi->file_path_atau_url && Storage::disk('public')->exists($materi->file_path_atau_url)) {
                Storage::disk('public')->delete($materi->file_path_atau_url);
            }
            $path = $request->file('file_materi')->store('materi', 'public');
            $materi->file_path_atau_url = $path;
        } elseif (in_array($request->tipe_materi, ['youtube', 'lainnya'])) {
            $materi->file_path_atau_url = $request->url_materi;
        }

        $materi->save();

        return redirect()->route('guru.materi.index')->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Materi $materi)
    {
        if ($materi->guru_id !== auth()->user()->guru->id) {
            abort(403);
        }

        if ($materi->tipe_materi === 'pdf' && $materi->file_path_atau_url) {
            Storage::disk('public')->delete($materi->file_path_atau_url);
        }

        $materi->delete();

        return redirect()->route('guru.materi.index')->with('success', 'Materi berhasil dihapus.');
    }
}
