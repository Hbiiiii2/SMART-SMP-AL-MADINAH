<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Services\CacheService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $data = CacheService::dashboardAdmin($today);

        return view('pages.admin.dashboard', ['title' => 'Dashboard Admin', 'data' => $data, 'today' => $today]);
    }
}
