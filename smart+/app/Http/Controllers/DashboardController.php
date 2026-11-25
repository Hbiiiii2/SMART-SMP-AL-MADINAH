<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
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

        return redirect()->route('login');
    }
}
