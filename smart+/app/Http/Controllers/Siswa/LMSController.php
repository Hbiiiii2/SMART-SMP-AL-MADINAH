<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Tugas;
use App\Models\PengumpulanTugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LMSController extends Controller
{
    public function showMateri(Materi $materi)
    {
        $siswa = auth()->user()->siswa;
        if (strtolower(trim($materi->kelas)) !== strtolower(trim($siswa->kelas))) {
            abort(403, "Akses ditolak. Materi ini bukan untuk kelas Anda.");
        }

        return view('pages.siswa.materi.show', compact('materi'));
    }

    public function showTugas(Tugas $tugas)
    {
        $siswa = auth()->user()->siswa;
        if (strtolower(trim($tugas->kelas)) !== strtolower(trim($siswa->kelas))) {
            abort(403, "Akses ditolak. Tugas ini bukan untuk kelas Anda.");
        }

        $pengumpulan = $tugas->pengumpulanTugas()->where('siswa_id', $siswa->id)->first();

        return view('pages.siswa.tugas.show', compact('tugas', 'pengumpulan'));
    }

    public function submitTugas(Request $request, Tugas $tugas)
    {
        $siswa = auth()->user()->siswa;
        if (strtolower(trim($tugas->kelas)) !== strtolower(trim($siswa->kelas))) {
            abort(403, "Akses ditolak. Tugas ini bukan untuk kelas Anda.");
        }

        $rules = [];
        $tipePengumpulan = explode(',', $tugas->tipe_pengumpulan);
        
        if (in_array('file', $tipePengumpulan)) {
            $rules['file_jawaban'] = 'nullable|file|max:10240';
        }
        if (in_array('link_url', $tipePengumpulan)) {
            $rules['link_url'] = 'nullable|url';
        }

        $request->validate($rules);

        // Pastikan ada sesuatu yang diisi jika salah satu wajib
        if (empty($request->file_jawaban) && empty($request->link_url)) {
            return back()->withErrors(['message' => 'Anda harus mengunggah file atau mengisi link URL'])->withInput();
        }

        $pengumpulan = $tugas->pengumpulanTugas()->where('siswa_id', $siswa->id)->first();

        if (!$pengumpulan) {
            $pengumpulan = new PengumpulanTugas();
            $pengumpulan->tugas_id = $tugas->id;
            $pengumpulan->siswa_id = $siswa->id;
        }

        if ($request->hasFile('file_jawaban') && in_array('file', $tipePengumpulan)) {
            if ($pengumpulan->file_path && Storage::disk('public')->exists($pengumpulan->file_path)) {
                Storage::disk('public')->delete($pengumpulan->file_path);
            }
            $path = $request->file('file_jawaban')->store('pengumpulan_tugas', 'public');
            $pengumpulan->file_path = $path;
        }

        if ($request->filled('link_url') && in_array('link_url', $tipePengumpulan)) {
            $pengumpulan->link_url = $request->link_url;
        }

        $pengumpulan->waktu_pengumpulan = now();
        $pengumpulan->status = 'dikumpulkan';
        $pengumpulan->save();

        return back()->with('success', 'Tugas berhasil dikumpulkan.');
    }
}
