<?php

namespace Database\Seeders\Budget;

use App\Models\Budget\BudgetPrevision;
use App\Models\Budget\Compte;
use App\Models\Budget\Service;
use Illuminate\Database\Seeder;

class BudgetPrevisionSeeder extends Seeder
{
    public function run(): void
    {
        $sgb = Service::query()->where('code', 'SGB')->first();
        $sa = Service::query()->where('code', 'SA')->first();
        $c661 = Compte::query()->where('numero', '661')->first();
        $c605 = Compte::query()->where('numero', '605')->first();
        $c618 = Compte::query()->where('numero', '618000')->first();

        if ($sgb && $c661) {
            BudgetPrevision::query()->updateOrCreate(
                ['service_id' => $sgb->id, 'compte_id' => $c661->id, 'annee' => 2025],
                ['montant_prevu' => 48_000_000],
            );
        }

        if ($sgb && $c605) {
            BudgetPrevision::query()->updateOrCreate(
                ['service_id' => $sgb->id, 'compte_id' => $c605->id, 'annee' => 2025],
                ['montant_prevu' => 3_500_000],
            );
        }

        if ($sa && $c618) {
            BudgetPrevision::query()->updateOrCreate(
                ['service_id' => $sa->id, 'compte_id' => $c618->id, 'annee' => 2025],
                ['montant_prevu' => 1_200_000],
            );
        }

        if ($sgb && $c618) {
            BudgetPrevision::query()->updateOrCreate(
                ['service_id' => $sgb->id, 'compte_id' => $c618->id, 'annee' => 2026],
                ['montant_prevu' => 1_500_000],
            );
        }
    }
}
