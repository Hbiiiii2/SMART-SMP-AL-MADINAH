<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('pages.auth.signin', ['title' => 'Masuk']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan gagal. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ])->onlyInput('email');
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60 * 5);

            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ])->onlyInput('email');
        }

        if ($user->isExpired()) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Masa aktif akun demo Anda telah berakhir pada ' . $user->expired_at->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB. Silakan hubungi administrator.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        LogAktivitas::create([
            'user_id' => $user->id,
            'aksi' => 'login',
            'deskripsi' => $user->name . ' berhasil masuk',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->redirectByRole();
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            LogAktivitas::create([
                'user_id' => $user->id,
                'aksi' => 'logout',
                'deskripsi' => $user->name . ' telah keluar',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function profile()
    {
        $user = Auth::user();

        return view('pages.auth.profile', [
            'title' => 'Profil Saya',
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only('name', 'email'));

        LogAktivitas::create([
            'user_id' => $user->id,
            'aksi' => 'update_profile',
            'deskripsi' => 'Profil berhasil diperbarui',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function changePassword()
    {
        return view('pages.auth.change-password', ['title' => 'Ubah Password']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        LogAktivitas::create([
            'user_id' => $user->id,
            'aksi' => 'change_password',
            'deskripsi' => 'Password berhasil diubah',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    private function redirectByRole()
    {
        $user = Auth::user();

        return match ($user->role) {
            'super_admin', 'admin_sekolah' => redirect()->route('admin.dashboard'),
            'kepala_sekolah' => redirect()->route('ks.dashboard'),
            'guru' => redirect()->route('guru.dashboard'),
            'direktur' => redirect()->route('direktur.dashboard'),
            'staff_akademik' => redirect()->route('staff.dashboard'),
            'siswa' => redirect()->route('siswa.dashboard'),
            'akun_demo' => redirect()->route('admin.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
