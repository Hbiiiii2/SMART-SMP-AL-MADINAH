<?php

use App\Models\HariLibur;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it identifies single day holiday', function () {
    HariLibur::create([
        'tanggal' => '2026-12-25',
        'nama' => 'Natal',
        'is_recurring' => false
    ]);

    expect(HariLibur::isLibur('2026-12-25'))->toBeTrue();
    expect(HariLibur::isLibur('2026-12-26'))->toBeFalse();
});

test('it identifies date range holiday', function () {
    HariLibur::create([
        'tanggal' => '2026-12-20',
        'tanggal_selesai' => '2026-12-25',
        'nama' => 'Libur Semester',
        'is_recurring' => false
    ]);

    expect(HariLibur::isLibur('2026-12-19'))->toBeFalse();
    expect(HariLibur::isLibur('2026-12-20'))->toBeTrue();
    expect(HariLibur::isLibur('2026-12-22'))->toBeTrue();
    expect(HariLibur::isLibur('2026-12-25'))->toBeTrue();
    expect(HariLibur::isLibur('2026-12-26'))->toBeFalse();
});

test('it identifies recurring single day holiday', function () {
    HariLibur::create([
        'tanggal' => '2020-01-01',
        'nama' => 'Tahun Baru',
        'is_recurring' => true
    ]);

    expect(HariLibur::isLibur('2026-01-01'))->toBeTrue();
    expect(HariLibur::isLibur('2027-01-01'))->toBeTrue();
    expect(HariLibur::isLibur('2026-01-02'))->toBeFalse();
});

test('it identifies recurring date range holiday across new year', function () {
    HariLibur::create([
        'tanggal' => '2020-12-25',
        'tanggal_selesai' => '2021-01-05',
        'nama' => 'Libur Panjang',
        'is_recurring' => true
    ]);

    // Should be holiday during these dates on any year
    expect(HariLibur::isLibur('2026-12-24'))->toBeFalse();
    expect(HariLibur::isLibur('2026-12-25'))->toBeTrue(); // Start date
    expect(HariLibur::isLibur('2026-12-31'))->toBeTrue(); // Middle of range
    expect(HariLibur::isLibur('2027-01-01'))->toBeTrue(); // Middle of range across year
    expect(HariLibur::isLibur('2027-01-05'))->toBeTrue(); // End date
    expect(HariLibur::isLibur('2027-01-06'))->toBeFalse();
});

test('it identifies recurring date range holiday within same month', function () {
    HariLibur::create([
        'tanggal' => '2020-05-01',
        'tanggal_selesai' => '2020-05-05',
        'nama' => 'Libur Awal Mei',
        'is_recurring' => true
    ]);

    expect(HariLibur::isLibur('2026-04-30'))->toBeFalse();
    expect(HariLibur::isLibur('2026-05-01'))->toBeTrue();
    expect(HariLibur::isLibur('2026-05-03'))->toBeTrue();
    expect(HariLibur::isLibur('2026-05-05'))->toBeTrue();
    expect(HariLibur::isLibur('2026-05-06'))->toBeFalse();
});
