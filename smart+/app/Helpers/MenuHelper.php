<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MenuHelper
{
    private const CACHE_TTL = 3600;

    public static function getMenuGroups(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        if ($user->role === 'guru') {
            return self::getGuruMenuGroups();
        }

        $cacheKey = 'sidebar_menu_v2_' . $user->role;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return match ($user->role) {
                'super_admin', 'admin_sekolah' => self::getAdminMenuGroups(),
                'kepala_sekolah' => self::getKepalaSekolahMenuGroups(),
                'siswa' => self::getSiswaMenuGroups(),
                'direktur' => self::getDirekturMenuGroups(),
                'staff_akademik' => self::getStaffAkademikMenuGroups(),
                default => [],
            };
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('sidebar_menu_super_admin');
        Cache::forget('sidebar_menu_admin_sekolah');
        Cache::forget('sidebar_menu_kepala_sekolah');
        Cache::forget('sidebar_menu_guru');
        Cache::forget('sidebar_menu_siswa');
        Cache::forget('sidebar_menu_direktur');
        Cache::forget('sidebar_menu_staff_akademik');
    }

    public static function getAdminMenuGroups(): array
    {
        $user = Auth::user();
        $isSuperAdmin = $user && $user->role === 'super_admin';

        $latestUpdate = \App\Models\UpdateHistory::where('is_published', true)->orderBy('tanggal', 'desc')->first();
        $latestVersion = $latestUpdate ? $latestUpdate->versi : '';


        $groups = [
            [
                'title' => 'Menu Utama',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/admin/dashboard'],
                    ['icon' => 'report', 'name' => 'Laporan Kehadiran', 'path' => '/admin/laporan'],
                ],
            ],
            [
                'title' => 'E-Rapor Terintegrasi',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard E-Rapor', 'path' => '/erapor', 'new' => true],
                    ['icon' => 'book', 'name' => 'Buku Nilai Mapel', 'path' => '/erapor/buku-nilai'],
                    ['icon' => 'sync', 'name' => 'Integrasi CBT', 'path' => '/erapor/cbt'],
                    ['icon' => 'report', 'name' => 'Capaian Kompetensi', 'path' => '/erapor/capaian-kompetensi', 'new' => true],
                    ['icon' => 'rekap', 'name' => 'Rekap & Peringkat STS', 'path' => '/erapor/recap'],
                    ['icon' => 'guru', 'name' => 'Catatan Wali Kelas', 'path' => '/erapor/cetak-rapor/catatan-wali-kelas', 'new' => true],
                    ['icon' => 'report', 'name' => 'Cetak Rapor Siswa', 'path' => '/erapor/cetak-rapor', 'new' => true],
                    ['icon' => 'settings', 'name' => 'Format & TTD Rapor', 'path' => '/erapor/cetak-rapor/pengaturan', 'new' => true],
                    ['icon' => 'siswa', 'name' => 'Buku Induk & Transkrip', 'path' => '/erapor/buku-induk', 'new' => true],
                ],
            ],
            [
                'title' => 'Manajemen Pengguna',
                'items' => [
                    ['icon' => 'calendar', 'name' => 'Tahun Ajaran', 'path' => '/admin/tahun-ajaran'],
                    [
                        'icon' => 'guru',
                        'name' => 'Manajemen Guru',
                        'subItems' => [
                            ['name' => 'Index Guru', 'path' => '/admin/guru'],
                            ['name' => 'Create Guru', 'path' => '/admin/guru/create'],
                        ]
                    ],
                    [
                        'icon' => 'siswa',
                        'name' => 'Manajemen Siswa',
                        'subItems' => [
                            ['name' => 'Index Siswa', 'path' => '/admin/siswa'],
                            ['name' => 'Create Siswa', 'path' => '/admin/siswa/create'],
                            ['name' => 'Kenaikan Kelas', 'path' => '/admin/siswa/kenaikan-kelas', 'new' => true],
                        ]
                    ],
                ],
            ],
            [
                'title' => 'Manajemen Absensi',
                'items' => [
                    ['icon' => 'absensi', 'name' => 'Absensi Guru', 'path' => '/kepala-sekolah/monitoring-guru'],
                    ['icon' => 'absensi', 'name' => 'Absensi Siswa', 'path' => '/kepala-sekolah/monitoring-siswa'],
                    ['icon' => 'rekap', 'name' => 'Rekap Absensi', 'path' => '/kepala-sekolah/rekap'],
                ],
            ],
            [
                'title' => 'Manajemen Jadwal',
                'items' => [
                    ['icon' => 'jadwal', 'name' => 'Jadwal Pelajaran', 'path' => '/admin/jadwal'],
                    ['icon' => 'book', 'name' => 'Mata Pelajaran', 'path' => '/admin/mata-pelajaran'],
                ],
            ],
            [
                'title' => 'Manajemen Kartu',
                'items' => [
                    [
                        'icon' => 'rfid',
                        'name' => 'Kartu RFID',
                        'subItems' => [
                            ['name' => 'Index Kartu', 'path' => '/admin/rfid'],
                            ['name' => 'Create Kartu', 'path' => '/admin/rfid/create'],
                        ]
                    ],
                ],
            ],
            [
                'title' => 'Manajemen Kalender Akademik',
                'items' => [
                    [
                        'icon' => 'kalender',
                        'name' => 'Kalender Akademik',
                        'path' => '/kalender'
                    ],
                    [
                        'icon' => 'kalender',
                        'name' => 'Tanggal Akademik',
                        'subItems' => [
                            ['name' => 'Index Tanggal Akademik', 'path' => '/admin/tanggal-akademik'],
                            ['name' => 'Create Tanggal Akademik', 'path' => '/admin/tanggal-akademik/create'],
                        ]
                    ],
                    [
                        'icon' => 'holiday',
                        'name' => 'Hari Libur',
                        'subItems' => [
                            ['name' => 'Index Hari Libur', 'path' => '/admin/hari-libur'],
                            ['name' => 'Create Hari Libur', 'path' => '/admin/hari-libur/create'],
                        ]
                    ],
                ],
            ],
            [
                'title' => 'Manajemen Sekolah',
                'items' => [
                    ['icon' => 'settings', 'name' => 'Pengaturan Sekolah', 'path' => '/admin/pengaturan-sekolah'],
                    ['icon' => 'location', 'name' => 'Lokasi', 'path' => '/admin/lokasi'],
                    ['icon' => 'database', 'name' => 'Backup Data', 'path' => '/admin/backup'],
                    ['icon' => 'history', 'name' => 'Update History', 'path' => '/admin/update-history', 'badge' => $latestVersion],
                ],
            ],
        ];

        if ($isSuperAdmin) {
            $groups[] = [
                'title' => 'Manajemen Pengguna Super_admin',
                'items' => [
                    [
                        'icon' => 'users',
                        'name' => 'Kelola Pengguna',
                        'subItems' => [
                            ['name' => 'Index', 'path' => '/admin/users'],
                            ['name' => 'Create', 'path' => '/admin/users/create'],
                        ]
                    ],
                    [
                        'icon' => 'activity',
                        'name' => 'Log Aktivitas',
                        'path' => '/admin/log-aktivitas'
                    ]
                ],
            ];
        }

        return $groups;
    }

    public static function getGuruMenuGroups(): array
    {
        $user = Auth::user();
        $guruId = $user?->guru?->id;
        $isWaliKelas = $guruId
            ? \App\Models\ClassHomeroom::where('guru_id', $guruId)->exists()
            : false;

        $eraporItems = [
            ['icon' => 'book', 'name' => 'Buku Nilai Mapel', 'path' => '/erapor/buku-nilai'],
            ['icon' => 'report', 'name' => 'Capaian Kompetensi', 'path' => '/erapor/capaian-kompetensi', 'new' => true],
            ['icon' => 'rekap', 'name' => 'Rekap & Peringkat STS', 'path' => '/erapor/recap'],
        ];

        if ($isWaliKelas) {
            $eraporItems[] = ['icon' => 'guru', 'name' => 'Catatan Wali Kelas', 'path' => '/erapor/cetak-rapor/catatan-wali-kelas', 'new' => true];
            $eraporItems[] = ['icon' => 'report', 'name' => 'Cetak Rapor Siswa', 'path' => '/erapor/cetak-rapor', 'new' => true];
        }

        return [
            [
                'title' => 'Menu Utama',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/guru/dashboard'],
                    ['icon' => 'absensi', 'name' => 'Absensi Saya', 'path' => '/guru/absensi-saya'],
                    ['icon' => 'absensi', 'name' => 'Absensi Siswa', 'path' => '/guru/absensi'],
                    ['icon' => 'jadwal', 'name' => 'Jadwal Saya', 'path' => '/guru/jadwal'],
                    ['icon' => 'holiday', 'name' => 'Pengajuan Cuti', 'path' => '/guru/cuti'],
                    ['icon' => 'report', 'name' => 'Laporan Kehadiran', 'path' => '/guru/laporan'],
                    ['icon' => 'kalender', 'name' => 'Kalender Akademik', 'path' => '/kalender'],
                ]
            ],
            [
                'title' => 'E-Learning (LMS)',
                'items' => [
                    ['icon' => 'book', 'name' => 'Materi Belajar', 'path' => '/guru/materi'],
                    ['icon' => 'report', 'name' => 'Tugas', 'path' => '/guru/tugas'],
                ]
            ],
            [
                'title' => 'E-Rapor Terintegrasi',
                'items' => $eraporItems,
            ]
        ];
    }

    public static function getSiswaMenuGroups(): array
    {
        return [
            [
                'title' => 'Menu Utama',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Jadwal Pelajaran', 'path' => '/siswa/dashboard'],
                    ['icon' => 'kalender', 'name' => 'Kalender Akademik', 'path' => '/kalender'],
                ]
            ]
        ];
    }

    public static function getKepalaSekolahMenuGroups(): array
    {
        return [
            [
                'title' => 'Menu Utama',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/kepala-sekolah/dashboard'],
                    ['icon' => 'absensi', 'name' => 'Absensi Saya', 'path' => '/kepala-sekolah/absensi-saya'],
                    ['icon' => 'monitor', 'name' => 'Monitoring Guru', 'path' => '/kepala-sekolah/monitoring-guru'],
                    ['icon' => 'monitor', 'name' => 'Monitoring Siswa', 'path' => '/kepala-sekolah/monitoring-siswa'],
                    ['icon' => 'holiday', 'name' => 'Approval Cuti', 'path' => '/kepala-sekolah/cuti'],
                    ['icon' => 'rekap', 'name' => 'Rekap Absensi', 'path' => '/kepala-sekolah/rekap'],
                    ['icon' => 'kalender', 'name' => 'Kalender Akademik', 'path' => '/kalender'],
                ]
            ],
            [
                'title' => 'E-Rapor & Arsip Sekolah',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard E-Rapor', 'path' => '/erapor'],
                    ['icon' => 'book', 'name' => 'Buku Nilai Mapel', 'path' => '/erapor/buku-nilai'],
                    ['icon' => 'sync', 'name' => 'Integrasi CBT', 'path' => '/erapor/cbt'],
                    ['icon' => 'report', 'name' => 'Capaian Kompetensi', 'path' => '/erapor/capaian-kompetensi'],
                    ['icon' => 'rekap', 'name' => 'Rekap & Peringkat STS', 'path' => '/erapor/recap'],
                    ['icon' => 'guru', 'name' => 'Catatan Wali Kelas', 'path' => '/erapor/cetak-rapor/catatan-wali-kelas'],
                    ['icon' => 'report', 'name' => 'Cetak Rapor Siswa', 'path' => '/erapor/cetak-rapor', 'new' => true],
                    ['icon' => 'settings', 'name' => 'Format & TTD Rapor', 'path' => '/erapor/cetak-rapor/pengaturan', 'new' => true],
                    ['icon' => 'siswa', 'name' => 'Buku Induk & Transkrip', 'path' => '/erapor/buku-induk', 'new' => true],
                ],
            ]
        ];
    }

    public static function getDirekturMenuGroups(): array
    {
        return [
            [
                'title' => 'Menu Utama',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/direktur/dashboard'],
                    ['icon' => 'holiday', 'name' => 'Approval Cuti', 'path' => '/direktur/cuti'],
                    ['icon' => 'kalender', 'name' => 'Kalender Akademik', 'path' => '/kalender'],
                ]
            ]
        ];
    }

    public static function getStaffAkademikMenuGroups(): array
    {
        return [
            [
                'title' => 'Menu Utama',
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/staff-akademik/dashboard'],
                    ['icon' => 'holiday', 'name' => 'Pengajuan Cuti', 'path' => '/staff-akademik/cuti'],
                    ['icon' => 'kalender', 'name' => 'Kalender Akademik', 'path' => '/kalender'],
                ]
            ]
        ];
    }

    public static function isActive($path): bool
    {
        return request()->is(ltrim($path, '/'));
    }

    public static function getIconSvg($iconName): string
    {
        $icons = [
            'dashboard' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z" fill="currentColor"></path></svg>',
            'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z" fill="currentColor"></path></svg>',
            'guru' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.5C7.30558 3.5 3.5 7.30558 3.5 12C3.5 14.1526 4.3002 16.1184 5.61936 17.616C6.17279 15.3096 8.24852 13.5955 10.7246 13.5955H13.2746C15.7509 13.5955 17.8268 15.31 18.38 17.6167C19.6996 16.119 20.5 14.153 20.5 12C20.5 7.30558 16.6944 3.5 12 3.5ZM17.0246 18.8566V18.8455C17.0246 16.7744 15.3457 15.0955 13.2746 15.0955H10.7246C8.65354 15.0955 6.97461 16.7744 6.97461 18.8455V18.856C8.38223 19.8895 10.1198 20.5 12 20.5C13.8798 20.5 15.6171 19.8898 17.0246 18.8566ZM2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12ZM11.9991 7.25C10.8847 7.25 9.98126 8.15342 9.98126 9.26784C9.98126 10.3823 10.8847 11.2857 11.9991 11.2857C13.1135 11.2857 14.0169 10.3823 14.0169 9.26784C14.0169 8.15342 13.1135 7.25 11.9991 7.25ZM8.48126 9.26784C8.48126 7.32499 10.0563 5.75 11.9991 5.75C13.9419 5.75 15.5169 7.32499 15.5169 9.26784C15.5169 11.2107 13.9419 12.7857 11.9991 12.7857C10.0563 12.7857 8.48126 11.2107 8.48126 9.26784Z" fill="currentColor"></path></svg>',
            'siswa' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.32L12 15l5-2.73v3.32z" fill="currentColor"/></svg>',
            'book' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'jadwal' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M2 8h20M8 2v4M16 2v4M10 12h4M8 16h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            'location' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="2"/></svg>',
            'rfid' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><line x1="2" y1="10" x2="22" y2="10" stroke="currentColor" stroke-width="2"/></svg>',
            'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-4v-.09A1.65 1.65 0 002.59 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 4v.09a1.65 1.65 0 00-1.51 1 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>',
            'report' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'holiday' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 14l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'kalender' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/><circle cx="8" cy="15" r="1" fill="currentColor"/><circle cx="12" cy="15" r="1" fill="currentColor"/><circle cx="16" cy="15" r="1" fill="currentColor"/></svg>',
            'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            'absensi' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="M9 14l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'monitor' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><line x1="8" y1="21" x2="16" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="17" x2="12" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            'rekap' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 16V8M11 16V11M15 16V5M19 16V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'sync' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'history' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'database' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><ellipse cx="12" cy="5" rx="9" ry="3" stroke="currentColor" stroke-width="2"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'activity' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M22 12h-4l-3 9L9 3l-3 9H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];

        return $icons[$iconName] ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="2" fill="currentColor"/></svg>';
    }
}
