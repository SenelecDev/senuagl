<?php

namespace Database\Seeders\Budget;

use App\Models\Budget\BudgetPrevision;
use App\Models\Budget\Compte;
use Illuminate\Database\Seeder;

class BudgetPrevisionSeeder extends Seeder
{
    public function run(): void
    {
        BudgetPrevision::query()
            ->whereHas('compte.enfants')
            ->delete();

        $c661101 = Compte::query()->where('numero', '661.101')->first();
        $c605500 = Compte::query()->where('numero', '605.500')->first();
        $c618100 = Compte::query()->where('numero', '618.100')->first();

        if ($c661101) {
            BudgetPrevision::query()->updateOrCreate(
                ['compte_id' => $c661101->id, 'annee' => 2025],
                ['montant_prevu' => 48_000_000],
            );
        }

        if ($c605500) {
            BudgetPrevision::query()->updateOrCreate(
                ['compte_id' => $c605500->id, 'annee' => 2025],
                ['montant_prevu' => 3_500_000],
            );
        }

        if ($c618100) {
            BudgetPrevision::query()->updateOrCreate(
                ['compte_id' => $c618100->id, 'annee' => 2025],
                ['montant_prevu' => 1_200_000],
            );

            BudgetPrevision::query()->updateOrCreate(
                ['compte_id' => $c618100->id, 'annee' => 2026],
                ['montant_prevu' => 1_500_000],
            );
        }
    }
}
