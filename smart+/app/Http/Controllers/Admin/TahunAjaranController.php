<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TahunAjaranRequest;
use App\Models\TahunAjaran;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;

class TahunAjaranController extends Controller
{
    use LogsActivity;
    public function index()
    {
        $tahunAjarans = TahunAjaran::latest('is_aktif')->latest('nama')->paginate(15);

        return view('pages.admin.tahun-ajaran.index', ['title' => 'Tahun Ajaran', 'tahunAjarans' => $tahunAjarans]);
    }

    public function create()
    {
        return view('pages.admin.tahun-ajaran.create', ['title' => 'Tambah Tahun Ajaran']);
    }

    public function store(TahunAjaranRequest $request)
    {
        CacheService::flushDropdowns();

        if ($request->boolean('is_aktif')) {
            TahunAjaran::where('is_aktif', true)->update(['is_aktif' => false]);
        }

        TahunAjaran::create($request->validated());

        $this->logActivity('create_tahun_ajaran');

        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit(TahunAjaran $tahun_ajaran)
    {
        return view('pages.admin.tahun-ajaran.edit', ['title' => 'Edit Tahun Ajaran', 'tahunAjaran' => $tahun_ajaran]);
    }

    public function update(TahunAjaranRequest $request, TahunAjaran $tahun_ajaran)
    {
        CacheService::flushDropdowns();

        if ($request->boolean('is_aktif')) {
            TahunAjaran::where('is_aktif', true)->where('id', '!=', $tahun_ajaran->id)->update(['is_aktif' => false]);
        }

        $tahun_ajaran->update($request->validated());

        $this->logActivity('update_tahun_ajaran');

        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(TahunAjaran $tahun_ajaran)
    {
        CacheService::flushDropdowns();

        $tahun_ajaran->delete();

        $this->logActivity('delete_tahun_ajaran');

        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil dihapus.');
    }

    public function setAktif(TahunAjaran $tahun_ajaran)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($tahun_ajaran) {
            TahunAjaran::where('is_aktif', true)->update(['is_aktif' => false]);
            $tahun_ajaran->update(['is_aktif' => true]);
        });

        $this->logActivity('set_aktif_tahun_ajaran', 'Mengaktifkan tahun ajaran: ' . $tahun_ajaran->nama);

        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Tahun ajaran ' . $tahun_ajaran->nama . ' diaktifkan.');
    }
}
