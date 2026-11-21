<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LokasiRequest;
use App\Models\Lokasi;
use App\Services\CacheService;
use App\Traits\LogsActivity;

class LokasiController extends Controller
{
    use LogsActivity;
    public function index()
    {
        $lokasis = Lokasi::with('users')->latest()->paginate(15);

        return view('pages.admin.lokasi.index', ['title' => 'Lokasi', 'lokasis' => $lokasis]);
    }

    public function create()
    {
        $users = \App\Models\User::whereIn('role', ['guru', 'staff_akademik', 'kepala_sekolah'])->get();
        return view('pages.admin.lokasi.create', ['title' => 'Tambah Lokasi', 'users' => $users]);
    }

    public function store(LokasiRequest $request)
    {
        CacheService::flushDropdowns();

        $lokasi = Lokasi::create($request->validated());
        if ($request->has('user_ids')) {
            $lokasi->users()->sync($request->user_ids);
        }

        $this->logActivity('create_lokasi');

        return redirect()->route('admin.lokasi.index')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(Lokasi $lokasi)
    {
        $users = \App\Models\User::whereIn('role', ['guru', 'staff_akademik', 'kepala_sekolah'])->get();
        $lokasi->load('users');
        return view('pages.admin.lokasi.edit', ['title' => 'Edit Lokasi', 'lokasi' => $lokasi, 'users' => $users]);
    }

    public function update(LokasiRequest $request, Lokasi $lokasi)
    {
        CacheService::flushDropdowns();

        $lokasi->update($request->validated());
        if ($request->has('user_ids')) {
            $lokasi->users()->sync($request->user_ids);
        } else {
            $lokasi->users()->sync([]);
        }

        $this->logActivity('update_lokasi');

        return redirect()->route('admin.lokasi.index')->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Lokasi $lokasi)
    {
        CacheService::flushDropdowns();

        $lokasi->delete();

        $this->logActivity('delete_lokasi');

        return redirect()->route('admin.lokasi.index')->with('success', 'Lokasi berhasil dihapus.');
    }
}
