<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GF;

class GFSeeder extends Seeder
{

public function run()
    {
        $grades = ['GF05','GF06','GF07','GF08','GF09','GF10','GF11','GF12','GF13','GF14','GF15', 'U1', 'U2', 'U3', 'U4'];
        foreach ($grades as $i => $code) {
            GF::create(['id_gf' => $code, 'ordre' => $i+1]);
        }
    }
}
