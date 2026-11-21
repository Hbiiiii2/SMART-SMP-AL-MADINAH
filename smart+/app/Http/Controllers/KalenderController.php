<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Jadwal;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KalenderController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->year ?? Carbon::now('Asia/Jakarta')->year);
        $month = (int) ($request->month ?? Carbon::now('Asia/Jakarta')->month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $rawHariLiburs = HariLibur::where(function($query) use ($startOfMonth, $endOfMonth) {
            $query->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                  ->orWhereBetween('tanggal_selesai', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                  ->orWhere(function($q) use ($startOfMonth, $endOfMonth) {
                      $q->where('tanggal', '<', $startOfMonth->toDateString())
                        ->where('tanggal_selesai', '>', $endOfMonth->toDateString());
                  });
        })->get();

        $recurringLiburs = HariLibur::where('is_recurring', true)->get();
        $hariLiburs = collect();
        
        $currDate = $startOfMonth->copy()->startOfWeek();
        $endLoop = $endOfMonth->copy()->endOfWeek();
        
        while ($currDate->lte($endLoop)) {
            $d = $currDate->copy();
            $matchedLibur = null;
            
            foreach ($rawHariLiburs as $libur) {
                $start = $libur->tanggal->copy()->startOfDay();
                $end = $libur->tanggal_selesai ? $libur->tanggal_selesai->copy()->endOfDay() : $start->copy()->endOfDay();
                if ($d->between($start, $end)) {
                    $matchedLibur = $libur;
                    break;
                }
            }
            
            if (!$matchedLibur) {
                foreach ($recurringLiburs as $libur) {
                    $startMonth = (int) $libur->tanggal->format('m');
                    $startDay = (int) $libur->tanggal->format('d');
                    if (!$libur->tanggal_selesai) {
                        if ($startMonth === $d->month && $startDay === $d->day) {
                            $matchedLibur = $libur;
                            break;
                        }
                    } else {
                        $start = Carbon::create($d->year, $startMonth, $startDay)->startOfDay();
                        $endMonth = (int) $libur->tanggal_selesai->format('m');
                        $endDay = (int) $libur->tanggal_selesai->format('d');
                        $end = Carbon::create($d->year, $endMonth, $endDay)->endOfDay();
                        
                        if ($end->lt($start)) {
                            if ($d->between($start->copy(), $end->copy()->addYear()) || $d->between($start->copy()->subYear(), $end->copy())) {
                                $matchedLibur = $libur;
                                break;
                            }
                        } else {
                            if ($d->between($start, $end)) {
                                $matchedLibur = $libur;
                                break;
                            }
                        }
                    }
                }
            }
            
            if ($matchedLibur) {
                $hariLiburs->put($d->format('Y-m-d'), $matchedLibur);
            }
            $currDate->addDay();
        }

        $jadwalCounts = Jadwal::selectRaw('hari, count(*) as total')
            ->groupBy('hari')
            ->pluck('total', 'hari');

        $absensiHarian = AbsensiSiswa::whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->selectRaw('tanggal,
                COUNT(CASE WHEN status IN ("Hadir","Terlambat") THEN 1 END) as hadir,
                COUNT(CASE WHEN status = "Alfa" THEN 1 END) as alfa')
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $rawTanggalAkademik = \App\Models\TanggalAkademik::where(function($query) use ($startOfMonth, $endOfMonth) {
            $query->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                  ->orWhereBetween('tanggal_selesai', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                  ->orWhere(function($q) use ($startOfMonth, $endOfMonth) {
                      $q->where('tanggal', '<', $startOfMonth->toDateString())
                        ->where('tanggal_selesai', '>', $endOfMonth->toDateString());
                  });
        })->get();

        $tanggalAkademikMap = [];
        foreach ($rawTanggalAkademik as $agenda) {
            $start = $agenda->tanggal->copy();
            $end = $agenda->tanggal_selesai ? $agenda->tanggal_selesai->copy() : $agenda->tanggal->copy();
            
            $curr = $start->copy();
            while ($curr->lte($end)) {
                $dateString = $curr->format('Y-m-d');
                if (!isset($tanggalAkademikMap[$dateString])) {
                    $tanggalAkademikMap[$dateString] = collect();
                }
                $tanggalAkademikMap[$dateString]->push($agenda);
                $curr->addDay();
            }
        }

        $calendarDays = [];
        $current = $startOfMonth->copy()->startOfWeek();

        while ($current->lte($endOfMonth->copy()->endOfWeek())) {
            $dateStr = $current->format('Y-m-d');
            $calendarDays[] = [
                'date' => $current->copy(),
                'dateStr' => $dateStr,
                'day' => $current->day,
                'isCurrentMonth' => $current->month === $month,
                'isToday' => $current->isToday(),
                'isLibur' => $hariLiburs->has($dateStr),
                'liburNama' => $hariLiburs->get($dateStr)?->nama ?? null,
                'jadwalCount' => $jadwalCounts->get($current->englishDayOfWeek) ?? 0,
                'absensi' => $absensiHarian->get($dateStr) ? ['hadir' => $absensiHarian->get($dateStr)->hadir, 'alfa' => $absensiHarian->get($dateStr)->alfa] : null,
                'agenda' => $tanggalAkademikMap[$dateStr] ?? collect(),
            ];
            $current->addDay();
        }

        $prevMonth = $startOfMonth->copy()->subMonth();
        $nextMonth = $startOfMonth->copy()->addMonth();

        return view('pages.kalender.index', [
            'title' => 'Kalender Akademik',
            'calendarDays' => $calendarDays,
            'year' => $year,
            'month' => $month,
            'monthName' => $startOfMonth->translatedFormat('F Y'),
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'hariLiburs' => $hariLiburs->unique('id'),
        ]);
    }
}
