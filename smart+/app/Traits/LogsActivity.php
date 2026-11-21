<?php

namespace App\Traits;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;

trait LogsActivity
{
    public function logActivity(string $aksi, string $deskripsi = '', ?Request $request = null): void
    {
        $request = $request ?? request();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
