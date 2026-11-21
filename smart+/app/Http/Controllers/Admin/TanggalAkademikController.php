<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TanggalAkademik;
use Illuminate\Http\Request;

class TanggalAkademikController extends Controller
{
    public function index()
    {
        $tanggalAkademik = TanggalAkademik::orderBy('tanggal', 'desc')->get();
        return view('pages.admin.tanggal-akademik.index', [
            'title' => 'Tanggal Akademik',
            'tanggalAkademik' => $tanggalAkademik
        ]);
    }

    public function create()
    {
        return view('pages.admin.tanggal-akademik.create', [
            'title' => 'Tambah Tanggal Akademik'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal',
            'nama_kegiatan' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'warna' => 'nullable|string',
        ]);

        TanggalAkademik::create($request->all());

        return redirect()->route('admin.tanggal-akademik.index')->with('success', 'Tanggal Akademik berhasil ditambahkan');
    }

    public function edit(TanggalAkademik $tanggalAkademik)
    {
        return view('pages.admin.tanggal-akademik.edit', [
            'title' => 'Edit Tanggal Akademik',
            'tanggalAkademik' => $tanggalAkademik
        ]);
    }

    public function update(Request $request, TanggalAkademik $tanggalAkademik)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal',
            'nama_kegiatan' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'warna' => 'nullable|string',
        ]);

        $tanggalAkademik->update($request->all());

        return redirect()->route('admin.tanggal-akademik.index')->with('success', 'Tanggal Akademik berhasil diperbarui');
    }

    public function destroy(TanggalAkademik $tanggalAkademik)
    {
        $tanggalAkademik->delete();
        return redirect()->route('admin.tanggal-akademik.index')->with('success', 'Tanggal Akademik berhasil dihapus');
    }
}
