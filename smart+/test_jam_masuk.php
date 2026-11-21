<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jam_masuks = \App\Models\AbsensiSiswa::pluck('jam_masuk')->toArray();
$unique = array_unique(array_map(function($val) {
    return (string)$val;
}, $jam_masuks));
echo json_encode(array_values($unique));
