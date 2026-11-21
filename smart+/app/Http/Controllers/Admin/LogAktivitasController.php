<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogAktivitasController extends Controller
{
    public function index(Request $request)
    {
        $query = LogAktivitas::with('user');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('aksi', 'like', "%{$s}%")
                    ->orWhere('deskripsi', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->aksi_filter) {
            $query->where('aksi', $request->aksi_filter);
        }

        if ($request->user_filter) {
            $query->where('user_id', $request->user_filter);
        }

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $aksiList = LogAktivitas::distinct()->pluck('aksi')->sort()->values();

        $userList = \App\Models\User::whereHas('logs')->orderBy('name')->get();

        return view('pages.admin.log-aktivitas.index', [
            'title' => 'Log Aktivitas',
            'logs' => $logs,
            'aksiList' => $aksiList,
            'userList' => $userList,
        ]);
    }

    public function show(LogAktivitas $log_aktivitas)
    {
        $log_aktivitas->load('user');

        return view('pages.admin.log-aktivitas.show', [
            'title' => 'Detail Log',
            'log' => $log_aktivitas,
        ]);
    }

    public function laravelLogs(Request $request)
    {
        $logPath = storage_path('logs');
        $files = collect();

        if (File::isDirectory($logPath)) {
            $logFiles = File::files($logPath);
            foreach ($logFiles as $file) {
                if ($file->getExtension() === 'log') {
                    $files->push([
                        'name' => $file->getFilename(),
                        'size' => $file->getSize(),
                        'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                        'modified_timestamp' => $file->getMTime(),
                    ]);
                }
            }
        }

        $files = $files->sortByDesc('modified_timestamp')->values();

        $selectedFile = $request->file;
        $logContent = null;

        if ($selectedFile) {
            $filePath = $logPath . '/' . basename($selectedFile);
            if (File::exists($filePath)) {
                $content = File::get($filePath);
                $lines = explode("\n", $content);
                $lines = array_reverse($lines);
                $logContent = array_slice($lines, 0, 200);
            }
        }

        return view('pages.admin.log-aktivitas.laravel-logs', [
            'title' => 'Laravel Log Files',
            'files' => $files,
            'selectedFile' => $selectedFile,
            'logContent' => $logContent,
        ]);
    }
}
