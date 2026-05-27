<?php

namespace Database\Seeders\Budget;

use App\Models\Budget\Compte;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            ['numero' => 'SECTION-AUTRES-ACHATS', 'intitule' => 'AUTRES ACHATS'],
            ['numero' => '605.500', 'intitule' => 'ACHATS DIRECT IMPRIM & FOURNI.', 'parent' => 'SECTION-AUTRES-ACHATS'],
            ['numero' => '605.510', 'intitule' => 'ACHATS DIRECT FOURNI INFORMATI', 'parent' => 'SECTION-AUTRES-ACHATS'],
            ['numero' => '605.600', 'intitule' => 'ACHATS DIRECT DE PETIT MAT.', 'parent' => 'SECTION-AUTRES-ACHATS'],

            ['numero' => 'SECTION-TRANSPORTS', 'intitule' => 'TRANSPORTS'],
            ['numero' => '618.100', 'intitule' => 'VOYAGES & DEPLT MISSION HORS SE', 'parent' => 'SECTION-TRANSPORTS'],
            ['numero' => '618.110', 'intitule' => 'VOYAGES & DEPLT PERSONEL MISSION', 'parent' => 'SECTION-TRANSPORTS'],

            ['numero' => 'SECTION-SERVICES-EXTERIEURS', 'intitule' => 'SERVICES EXTERIEURS'],
            ['numero' => '621.000', 'intitule' => 'TRAVAUX ET SCES EXTERIEURS', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '622.320', 'intitule' => 'LOCATION DE MATERIEL INFORMATIQ (infogerance)', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '624.310', 'intitule' => 'MAINTENANCE REPARAT. MATERIEL I', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '626.500', 'intitule' => 'DOCUMENTATION GENERALE', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '626.600', 'intitule' => 'DOCUMENTATION TECHNIQUE', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '628.800', 'intitule' => 'FRAIS DE TELECOMMUNICATION', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '632.440', 'intitule' => "PRESTATIONS D'EXPERTISE", 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '633.000', 'intitule' => 'FRAIS RECYCLAGES ET FORMATIONS', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '634.300', 'intitule' => 'REDEVANCES POUR LOGICIELS', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],
            ['numero' => '638.302', 'intitule' => 'PAUSES CAFE ET RESTAURATION', 'parent' => 'SECTION-SERVICES-EXTERIEURS'],

            ['numero' => 'SECTION-CHARGES-PERSONNEL', 'intitule' => 'CHARGES DE PERSONNEL'],
            ['numero' => '661.101', 'intitule' => 'Heures Supplémentaires', 'parent' => 'SECTION-CHARGES-PERSONNEL'],
        ];

        $numeros = collect($comptes)->pluck('numero')->all();

        Compte::query()
            ->whereNotIn('numero', $numeros)
            ->whereDoesntHave('budgetPrevisions')
            ->whereDoesntHave('realisations')
            ->delete();

        foreach ($comptes as $compte) {
            Compte::query()->updateOrCreate(
                ['numero' => $compte['numero']],
                ['intitule' => $compte['intitule']],
            );
        }

        foreach ($comptes as $compte) {
            $parentNumero = $compte['parent'] ?? null;
            Compte::query()
                ->where('numero', $compte['numero'])
                ->update([
                    'parent_id' => $parentNumero
                        ? Compte::query()->where('numero', $parentNumero)->value('id')
                        : null,
                ]);
        }
    }
}
