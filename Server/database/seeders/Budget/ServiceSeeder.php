<?php

namespace Database\Seeders\Budget;

use App\Models\Budget\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['code' => 'SGB', 'intitule' => 'Service Gestion et Budget'],
            ['code' => 'SGS', 'intitule' => 'Service Gestion des Stocks'],
            ['code' => 'SA', 'intitule' => 'Service Achats'],
            ['code' => 'SEG', 'intitule' => 'Service Entretien Général'],
            ['code' => 'SA2', 'intitule' => 'Service Administratif'],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(
                ['code' => $service['code']],
                ['intitule' => $service['intitule']],
            );
        }
    }
}
