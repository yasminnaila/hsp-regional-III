<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Period;
use App\Models\Region;
use Illuminate\Database\Seeder;

class HspMasterSeeder extends Seeder
{
    public function run(): void
    {
        Period::query()->updateOrCreate(['year' => 2026], ['name' => 'AHSP Tahun 2026', 'is_active' => true]);

        foreach ([
            ['code' => 'JATENG_DIY', 'name' => 'Jateng & DIY', 'sort_order' => 1],
            ['code' => 'JATIM', 'name' => 'Jawa Timur', 'sort_order' => 2],
            ['code' => 'BALI', 'name' => 'Bali', 'sort_order' => 3],
            ['code' => 'NTB_NTT', 'name' => 'NTB & NTT', 'sort_order' => 4],
        ] as $region) {
            Region::query()->updateOrCreate(['code' => $region['code']], $region + ['is_active' => true]);
        }

        $categories = [
            ['I', 'Pekerjaan Persiapan'], ['II', 'Pekerjaan Tanah'], ['III', 'Pekerjaan Pondasi'],
            ['IV', 'Pekerjaan Beton'], ['V', 'Pekerjaan Baja'], ['VI', 'Pekerjaan Dinding'],
            ['VII', 'Pekerjaan Kusen, Pintu dan Jendela'], ['VIII', 'Pekerjaan Plafon'],
            ['IX', 'Pekerjaan Lantai'], ['X', 'Pekerjaan Atap'], ['XI', 'Pekerjaan Elektrikal'],
            ['XII', 'Pekerjaan Mekanikal'], ['XIII', 'Pekerjaan Sanitasi'],
            ['XIV', 'Pekerjaan Pengecatan'], ['XV', 'Pekerjaan Perkerasan'], ['XVI', 'Pekerjaan Lain-lain'],
        ];

        foreach ($categories as $index => [$code, $name]) {
            Category::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }
}
