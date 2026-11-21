<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RfidCardRequest;
use App\Models\Guru;
use App\Models\RfidCard;
use App\Models\Siswa;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    use LogsActivity;
    public function index(Request $request)
    {
        $rfidCards = RfidCard::with('siswa.user', 'guru.user')
            ->when($request->tipe, function ($q, $tipe) {
                if ($tipe === 'siswa') $q->whereNotNull('siswa_id');
                elseif ($tipe === 'guru') $q->whereNotNull('guru_id');
            })
            ->when($request->search, function ($q, $s) {
                $q->where('uid', 'like', "%{$s}%")
                    ->orWhereHas('siswa', fn ($sq) => $sq->where('nama_lengkap', 'like', "%{$s}%")->orWhere('nisn', 'like', "%{$s}%"))
                    ->orWhereHas('guru', fn ($gq) => $gq->where('nama_lengkap', 'like', "%{$s}%")->orWhere('nip', 'like', "%{$s}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.rfid.index', ['title' => 'Kartu RFID', 'rfidCards' => $rfidCards]);
    }

    public function create()
    {
        return view('pages.admin.rfid.create', [
            'title' => 'Tambah Kartu RFID',
            'siswas' => Siswa::with('user')->get(),
            'gurus' => Guru::with('user')->get(),
        ]);
    }

    public function store(RfidCardRequest $request)
    {
        CacheService::flushDropdowns();

        $data = $request->validated();
        if (empty($data['siswa_id'])) unset($data['siswa_id']);
        if (empty($data['guru_id'])) unset($data['guru_id']);

        RfidCard::create($data);

        $this->logActivity('create_rfid');

        return redirect()->route('admin.rfid.index')->with('success', 'Kartu RFID berhasil ditambahkan.');
    }

    public function edit(RfidCard $rfid)
    {
        return view('pages.admin.rfid.edit', [
            'title' => 'Edit Kartu RFID',
            'rfid' => $rfid,
            'siswas' => Siswa::with('user')->get(),
            'gurus' => Guru::with('user')->get(),
        ]);
    }

    public function update(RfidCardRequest $request, RfidCard $rfid)
    {
        CacheService::flushDropdowns();

        $data = $request->validated();
        if (empty($data['siswa_id'])) $data['siswa_id'] = null;
        if (empty($data['guru_id'])) $data['guru_id'] = null;

        $rfid->update($data);

        $this->logActivity('update_rfid');

        return redirect()->route('admin.rfid.index')->with('success', 'Kartu RFID berhasil diperbarui.');
    }

    public function destroy(RfidCard $rfid)
    {
        CacheService::flushDropdowns();

        $rfid->delete();

        $this->logActivity('delete_rfid');

        return redirect()->route('admin.rfid.index')->with('success', 'Kartu RFID berhasil dihapus.');
    }
}
