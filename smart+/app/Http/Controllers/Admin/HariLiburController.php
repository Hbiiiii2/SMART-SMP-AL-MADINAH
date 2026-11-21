<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class HariLiburController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $hariLiburs = HariLibur::when($request->search, function ($q, $s) {
            $q->where('nama', 'like', "%{$s}%")->orWhere('tanggal', 'like', "%{$s}%");
        })
            ->orderByDesc('tanggal')
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.hari-libur.index', ['title' => 'Hari Libur', 'hariLiburs' => $hariLiburs]);
    }

    public function create()
    {
        return view('pages.admin.hari-libur.create', ['title' => 'Tambah Hari Libur']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal',
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_recurring' => 'boolean',
        ]);

        HariLibur::create($request->only('tanggal', 'tanggal_selesai', 'nama', 'keterangan', 'is_recurring'));
        CacheService::flushDropdowns();

        $this->logActivity('create_hari_libur', 'Menambah hari libur: ' . $request->nama);

        return redirect()->route('admin.hari-libur.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function edit(HariLibur $hari_libur)
    {
        return view('pages.admin.hari-libur.edit', ['title' => 'Edit Hari Libur', 'hariLibur' => $hari_libur]);
    }

    public function update(Request $request, HariLibur $hari_libur)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal',
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_recurring' => 'boolean',
        ]);

        $hari_libur->update($request->only('tanggal', 'tanggal_selesai', 'nama', 'keterangan', 'is_recurring'));
        CacheService::flushDropdowns();

        $this->logActivity('update_hari_libur', 'Mengubah hari libur: ' . $request->nama);

        return redirect()->route('admin.hari-libur.index')->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(HariLibur $hari_libur)
    {
        $nama = $hari_libur->nama;
        $hari_libur->delete();
        CacheService::flushDropdowns();

        $this->logActivity('delete_hari_libur', 'Menghapus hari libur: ' . $nama);

        return redirect()->route('admin.hari-libur.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}
