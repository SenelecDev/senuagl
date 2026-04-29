<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NRSeeder extends Seeder
{
    public function run()
    {
        $nrs = [];
        for ($i = 1; $i <= 100; $i++) {
            $code = 'NR' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $nrs[] = ['id_nr' => $code, 'ordre' => $i];
        }

        DB::table('nrs')->insert($nrs);
    }
}
