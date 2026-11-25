<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoAccountMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if ($request->routeIs('logout')) {
            return $next($request);
        }

        $user = Auth::user();

        if (!$user->is_active || $user->isExpired()) {
            $message = $user->isExpired()
                ? 'Masa aktif akun demo Anda telah berakhir pada ' . $user->expired_at->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB. Silakan hubungi administrator.'
                : 'Akun Anda telah dinonaktifkan. Hubungi administrator.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['message' => $message], 401);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        if ($user->isAkunDemo()) {
            if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mode Akun Demo hanya untuk preview (read-only). Perubahan data tidak diizinkan.',
                    ], 403);
                }

                abort(403, 'Mode Akun Demo hanya untuk preview (read-only). Aksi tambah, ubah, atau hapus data tidak diizinkan.');
            }

            $routeName = $request->route()?->getName();
            $allowedEditRoutes = ['admin.pengaturan-sekolah.edit', 'admin.profile.edit'];

            $isBlockedGetRoute = $request->routeIs('*.create')
                || ($request->routeIs('*.edit') && !in_array($routeName, $allowedEditRoutes, true))
                || $request->routeIs([
                    'admin.siswa.kenaikan-kelas',
                    'admin.password.edit',
                    'admin.backup.*',
                    'admin.users.*',
                    'admin.log-aktivitas.*',
                    'ks.absensi-saya.*',
                    'erapor.cbt.sync-api',
                ]);

            if ($isBlockedGetRoute) {
                abort(403, 'Mode Akun Demo hanya untuk preview (read-only). Halaman ini tidak dapat diakses pada mode demo.');
            }
        }

        return $next($request);
    }
}
