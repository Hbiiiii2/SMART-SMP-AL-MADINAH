<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuruRequest;
use App\Models\Guru;
use App\Models\User;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class GuruController extends Controller
{
    use LogsActivity;
    public function index(Request $request)
    {
        $gurus = Guru::with('user')
            ->when($request->search, fn ($q, $s) => $q->where('nama_lengkap', 'like', "%$s%")->orWhere('nip', 'like', "%$s%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.guru.index', ['title' => 'Data Guru', 'gurus' => $gurus]);
    }

    public function create()
    {
        return view('pages.admin.guru.create', ['title' => 'Tambah Guru']);
    }

    public function store(GuruRequest $request)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->nama_lengkap,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'guru',
                'is_active' => true,
            ]);

            $data = $request->validated();
            if ($request->hasFile('foto')) {
                $data['foto'] = $request->file('foto')->store('foto/guru', 'public');
            }
            unset($data['email'], $data['password']);

            $user->guru()->create($data);
        });

        $this->logActivity('create_guru', 'Menambah guru: ' . $request->nama_lengkap, $request);

        return redirect()->route('admin.guru.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function show(Guru $guru)
    {
        $guru->load('user', 'jadwals.mataPelajaran', 'jadwals.lokasi');

        return view('pages.admin.guru.show', ['title' => 'Detail Guru', 'guru' => $guru]);
    }

    public function edit(Guru $guru)
    {
        $guru->load('user');

        return view('pages.admin.guru.edit', ['title' => 'Edit Guru', 'guru' => $guru]);
    }

    public function update(GuruRequest $request, Guru $guru)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($request, $guru) {
            $guru->user->update([
                'name' => $request->nama_lengkap,
                'email' => $request->email,
            ]);

            $data = $request->validated();
            unset($data['email'], $data['password']);

            if ($request->hasFile('foto')) {
                if ($guru->foto) {
                    Storage::disk('public')->delete($guru->foto);
                }
                $data['foto'] = $request->file('foto')->store('foto/guru', 'public');
            }

            $guru->update($data);

            if ($request->filled('password')) {
                $guru->user->update(['password' => Hash::make($request->password)]);
            }
        });

        $this->logActivity('update_guru', 'Mengubah data guru: ' . $guru->nama_lengkap, $request);

        return redirect()->route('admin.guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Guru $guru)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($guru) {
            if ($guru->foto) {
                Storage::disk('public')->delete($guru->foto);
            }
            $guru->user->delete();
            $guru->delete();
        });

        $this->logActivity('delete_guru', 'Menghapus guru: ' . $guru->nama_lengkap);

        return redirect()->route('admin.guru.index')->with('success', 'Data guru berhasil dihapus.');
    }
}
