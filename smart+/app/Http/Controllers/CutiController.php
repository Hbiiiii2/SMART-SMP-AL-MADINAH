<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengajuanCuti;
use Illuminate\Support\Facades\Storage;

class CutiController extends Controller
{
    private function getIndexRoute()
    {
        $user = auth()->user();
        if ($user->isGuru()) return 'guru.cuti.index';
        if ($user->isStaffAkademik()) return 'staff.cuti.index';
        if ($user->isKepalaSekolah()) return 'ks.cuti.index';
        if ($user->isDirektur()) return 'direktur.cuti.index';
        return 'dashboard';
    }

    public function index()
    {
        $user = auth()->user();
        $cutiList = collect();

        if ($user->isGuru() || $user->isStaffAkademik()) {
            $cutiList = PengajuanCuti::where('user_id', $user->id)->latest()->get();
        } elseif ($user->isKepalaSekolah()) {
            // Kepala Sekolah manages Guru
            $cutiList = PengajuanCuti::whereHas('user', function ($query) {
                $query->where('role', 'guru');
            })->latest()->get();
        } elseif ($user->isDirektur()) {
            // Direktur manages Staff Akademik
            $cutiList = PengajuanCuti::whereHas('user', function ($query) {
                $query->where('role', 'staff_akademik');
            })->latest()->get();
        } elseif ($user->isAdmin() || $user->isSuperAdmin()) {
            $cutiList = PengajuanCuti::with('user')->latest()->get();
        }

        return view('pages.cuti.index', compact('cutiList'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user->isGuru() && !$user->isStaffAkademik()) {
            return redirect()->route($this->getIndexRoute())->with('error', 'Hanya Guru dan Staff Akademik yang dapat mengajukan cuti.');
        }

        return view('pages.cuti.create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->isGuru() && !$user->isStaffAkademik()) {
            return redirect()->back()->with('error', 'Tidak memiliki izin.');
        }

        $request->validate([
            'jenis_cuti' => 'required|string|max:50',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'tanggal_selesai.after_or_equal' => 'Silahkan isi kembali dengan benar untuk tanggal yang diajukan.',
        ]);

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('lampiran_cuti', 'public');
        }

        PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => $request->jenis_cuti,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'alasan' => $request->alasan,
            'lampiran' => $lampiranPath,
            'status' => 'pending',
        ]);

        return redirect()->route($this->getIndexRoute())->with('success', 'Pengajuan cuti berhasil dikirim.');
    }

    public function show(PengajuanCuti $cuti)
    {
        return view('pages.cuti.show', compact('cuti'));
    }

    public function process(Request $request, PengajuanCuti $cuti)
    {
        $user = auth()->user();
        
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'catatan_approver' => 'nullable|string',
        ]);

        // Cek wewenang
        $isAuthorized = false;
        if ($user->isKepalaSekolah() && $cuti->user->isGuru()) {
            $isAuthorized = true;
        } elseif ($user->isDirektur() && $cuti->user->isStaffAkademik()) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return redirect()->route($this->getIndexRoute())->with('error', 'Anda tidak memiliki wewenang untuk memproses pengajuan ini.');
        }

        $cuti->update([
            'status' => $request->status,
            'approver_id' => $user->id,
            'catatan_approver' => $request->catatan_approver,
        ]);

        // Jika disetujui, update tabel absensi_guru/staff untuk mengisi absen 'Izin' atau 'Sakit'
        if ($request->status === 'disetujui') {
            $startDate = \Carbon\Carbon::parse($cuti->tanggal_mulai);
            $endDate = \Carbon\Carbon::parse($cuti->tanggal_selesai);
            
            // Map jenis_cuti to status absensi
            $statusAbsen = 'Izin';
            if (in_array(strtolower($cuti->jenis_cuti), ['sakit', 'melahirkan'])) {
                $statusAbsen = 'Sakit';
            }

            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                // Skip weekends (Sabtu, Minggu)
                if ($date->isWeekend()) {
                    continue;
                }

                // Skip hari libur nasional
                $isLibur = \App\Models\HariLibur::where('tanggal', $date->toDateString())->exists();
                if ($isLibur) {
                    continue;
                }

                if ($cuti->user->isGuru() && $cuti->user->guru) {
                    \App\Models\AbsensiGuru::updateOrCreate(
                        ['guru_id' => $cuti->user->guru->id, 'tanggal' => $date->toDateString()],
                        ['status' => $statusAbsen, 'keterangan' => 'Cuti: ' . $cuti->jenis_cuti]
                    );
                } elseif ($cuti->user->isStaffAkademik() && $cuti->user->staff_akademik) {
                    \App\Models\AbsensiStaff::updateOrCreate(
                        ['staff_akademik_id' => $cuti->user->staff_akademik->id, 'tanggal' => $date->toDateString()],
                        ['status' => $statusAbsen, 'keterangan' => 'Cuti: ' . $cuti->jenis_cuti]
                    );
                }
            }
        }

        return redirect()->route($this->getIndexRoute())->with('success', 'Pengajuan cuti berhasil diproses.');
    }
}
