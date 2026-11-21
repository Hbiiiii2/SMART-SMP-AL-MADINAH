<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MataPelajaranRequest;
use App\Models\MataPelajaran;
use App\Services\CacheService;
use App\Traits\LogsActivity;

class MataPelajaranController extends Controller
{
    use LogsActivity;
    public function index()
    {
        $mataPelajarans = MataPelajaran::latest()->paginate(15);

        return view('pages.admin.mata-pelajaran.index', ['title' => 'Mata Pelajaran', 'mataPelajarans' => $mataPelajarans]);
    }

    public function create()
    {
        return view('pages.admin.mata-pelajaran.create', ['title' => 'Tambah Mata Pelajaran']);
    }

    public function store(MataPelajaranRequest $request)
    {
        CacheService::flushDropdowns();

        MataPelajaran::create($request->validated());

        $this->logActivity('create_mapel');

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(MataPelajaran $mata_pelajaran)
    {
        return view('pages.admin.mata-pelajaran.edit', ['title' => 'Edit Mata Pelajaran', 'mataPelajaran' => $mata_pelajaran]);
    }

    public function update(MataPelajaranRequest $request, MataPelajaran $mata_pelajaran)
    {
        CacheService::flushDropdowns();

        $mata_pelajaran->update($request->validated());

        $this->logActivity('update_mapel');

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(MataPelajaran $mata_pelajaran)
    {
        CacheService::flushDropdowns();

        $mata_pelajaran->delete();

        $this->logActivity('delete_mapel');

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
