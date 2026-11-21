<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'code' => 'uh',
                'name' => 'Ulangan Harian',
                'default_count' => 3,
                'default_weight' => 6.00,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'code' => 'tugas',
                'name' => 'Tugas',
                'default_count' => 6,
                'default_weight' => 6.00,
                'is_active' => true,
                'order' => 2,
            ],
            [
                'code' => 'praktek',
                'name' => 'Praktek',
                'default_count' => 2,
                'default_weight' => 6.00,
                'is_active' => true,
                'order' => 3,
            ],
            [
                'code' => 'projek',
                'name' => 'Projek',
                'default_count' => 2,
                'default_weight' => 6.00,
                'is_active' => true,
                'order' => 4,
            ],
            [
                'code' => 'portofolio',
                'name' => 'Portofolio',
                'default_count' => 2,
                'default_weight' => 6.00,
                'is_active' => true,
                'order' => 5,
            ],
            [
                'code' => 'sts',
                'name' => 'STS 1',
                'default_count' => 1,
                'default_weight' => 30.00,
                'is_active' => true,
                'order' => 6,
            ],
            [
                'code' => 'uas',
                'name' => 'UAS',
                'default_count' => 1,
                'default_weight' => 40.00,
                'is_active' => true,
                'order' => 7,
            ],
        ];

        foreach ($components as $component) {
            DB::table('assessment_components')->updateOrInsert(
                ['code' => $component['code']],
                array_merge($component, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }
}
