<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    use HasFactory;

    protected $table = 'hari_libur';

    protected $fillable = [
        'tanggal',
        'tanggal_selesai',
        'nama',
        'keterangan',
        'is_recurring',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_selesai' => 'date',
        'is_recurring' => 'boolean',
    ];

    public static function isLibur(string $date): bool
    {
        $d = Carbon::parse($date)->startOfDay();
        $dateStr = $d->format('Y-m-d');

        $directMatch = self::where(function($q) use ($dateStr) {
            $q->whereDate('tanggal', $dateStr)->whereNull('tanggal_selesai');
        })->orWhere(function($q) use ($dateStr) {
            $q->whereNotNull('tanggal_selesai')
              ->whereDate('tanggal', '<=', $dateStr)
              ->whereDate('tanggal_selesai', '>=', $dateStr);
        })->exists();

        if ($directMatch) {
            return true;
        }

        $recurring = self::where('is_recurring', true)->get();

        foreach ($recurring as $libur) {
            $startMonth = (int) $libur->tanggal->format('m');
            $startDay = (int) $libur->tanggal->format('d');

            if (!$libur->tanggal_selesai) {
                if ($startMonth === $d->month && $startDay === $d->day) {
                    return true;
                }
            } else {
                $start = Carbon::create($d->year, $startMonth, $startDay)->startOfDay();
                $endMonth = (int) $libur->tanggal_selesai->format('m');
                $endDay = (int) $libur->tanggal_selesai->format('d');
                $end = Carbon::create($d->year, $endMonth, $endDay)->endOfDay();

                if ($end->lt($start)) {
                    $startThisYear = $start->copy();
                    $endNextYear = $end->copy()->addYear();
                    
                    $startPrevYear = $start->copy()->subYear();
                    $endThisYear = $end->copy();
                    
                    if ($d->between($startThisYear, $endNextYear) || $d->between($startPrevYear, $endThisYear)) {
                        return true;
                    }
                } else {
                    if ($d->between($start, $end)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
