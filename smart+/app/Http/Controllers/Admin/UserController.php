<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use LogsActivity;
    public function index(Request $request)
    {
        $users = User::when($request->search, fn ($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"))
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.users.index', ['title' => 'Kelola Pengguna', 'users' => $users]);
    }

    public function create()
    {
        return view('pages.admin.users.create', ['title' => 'Tambah Pengguna']);
    }

    public function store(UserRequest $request)
    {
        CacheService::flushDropdowns();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($user->role === 'staff_akademik') {
            \App\Models\StaffAkademik::create([
                'pengguna_id' => $user->id,
                'nama_lengkap' => $user->name,
                'jenis_kelamin' => 'L', // Default, admin bisa ubah nanti
                'status_aktif' => true,
            ]);
        }

        $this->logActivity('create_user', 'Menambah pengguna: ' . $request->name, $request);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('pages.admin.users.edit', ['title' => 'Edit Pengguna', 'user' => $user]);
    }

    public function update(UserRequest $request, User $user)
    {
        CacheService::flushDropdowns();

        $data = $request->validated();
        if (!$request->filled('password')) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        $this->logActivity('update_user', 'Mengubah pengguna: ' . $user->name, $request);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        CacheService::flushDropdowns();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak bisa menghapus akun sendiri.']);
        }

        $user->delete();

        $this->logActivity('delete_user', 'Menghapus pengguna: ' . $user->name);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function toggleActive(User $user)
    {
        CacheService::flushDropdowns();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak bisa menonaktifkan akun sendiri.']);
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $this->logActivity('toggle_user', 'Toggle active pengguna: ' . $user->name);

        return redirect()->route('admin.users.index')->with('success', "Pengguna berhasil $status.");
    }
}
