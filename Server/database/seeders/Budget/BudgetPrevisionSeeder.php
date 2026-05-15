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
        BudgetPrevision::query()
            ->whereHas('compte.enfants')
            ->delete();

        $sgb = Service::query()->where('code', 'SGB')->first();
        $sa = Service::query()->where('code', 'SA')->first();
        $c647 = Compte::query()->where('numero', '647.000')->first();
        $c605100 = Compte::query()->where('numero', '605100')->first();
        $c618 = Compte::query()->where('numero', '618000')->first();

        if ($sgb && $c647) {
            BudgetPrevision::query()->updateOrCreate(
                ['service_id' => $sgb->id, 'compte_id' => $c647->id, 'annee' => 2025],
                ['montant_prevu' => 48_000_000],
            );
        }

        if ($sgb && $c605100) {
            BudgetPrevision::query()->updateOrCreate(
                ['service_id' => $sgb->id, 'compte_id' => $c605100->id, 'annee' => 2025],
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
