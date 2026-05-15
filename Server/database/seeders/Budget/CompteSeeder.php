<?php

namespace Database\Seeders\Budget;

use App\Models\Budget\Compte;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            ['numero' => '605', 'intitule' => 'EAU/ELECTRICITE/GAZ/SERVICES ET ACHATS DIVERS'],
            ['numero' => '605100', 'intitule' => 'EAU', 'parent' => '605'],
            ['numero' => '605200', 'intitule' => 'ELECTRICITE', 'parent' => '605'],
            ['numero' => '605300', 'intitule' => 'AUTRES ENERGIES (Carburant)', 'parent' => '605'],
            ['numero' => '605500', 'intitule' => 'FOURNIT DE BUREAU NON STOCKABLE', 'parent' => '605'],
            ['numero' => '605600', 'intitule' => 'PETIT MATERIEL ET OUTILLAGE', 'parent' => '605'],
            ['numero' => '605800', 'intitule' => 'TRAVAUX ET FRAIS DIVERS', 'parent' => '605'],
            ['numero' => '61.', 'intitule' => 'TRANSPORTS'],
            ['numero' => '614000', 'intitule' => 'TRANSPORTS DU PERSONNEL', 'parent' => '61.'],
            ['numero' => '618000', 'intitule' => 'VOYAGES ET DEPLACEMENTS', 'parent' => '61.'],
            ['numero' => '622', 'intitule' => 'LOCATIONS'],
            ['numero' => '622200', 'intitule' => 'LOCATION DE BATIMENTS', 'parent' => '622'],
            ['numero' => '622800', 'intitule' => 'LOCATIONS ET CHARG LOC DIVERSES', 'parent' => '622'],
            ['numero' => '624', 'intitule' => 'ENTRETIENS ET REPARATIONS'],
            ['numero' => '624100', 'intitule' => 'ENTRETIEN ET REPARAT BIENS IMMOB', 'parent' => '624'],
            ['numero' => '624200', 'intitule' => 'ENTRETIEN ET REPARAT BIENS MOBIL', 'parent' => '624'],
            ['numero' => '624800', 'intitule' => 'AUTRES ENTRETIENS ET REPARATIONS', 'parent' => '624'],
            ['numero' => '625.200', 'intitule' => 'ASSURANCES MATERIEL DE TRANSPT'],
            ['numero' => '626.500', 'intitule' => 'DOCUMENTATION GENERALE'],
            ['numero' => '627.600', 'intitule' => 'CADEAUX ET DONS'],
            ['numero' => '628.000', 'intitule' => 'FRAIS DE TELECOMMUNICATIONS'],
            ['numero' => '631.100', 'intitule' => 'FRAIS BANCAIRES'],
            ['numero' => '632.400', 'intitule' => 'HONORAIRES'],
            ['numero' => '638', 'intitule' => 'FRAIS LIES A LA RESTAURATION, MISSIONS ET RECEPTIONS'],
            ['numero' => '633.000', 'intitule' => 'FRAIS DE FORMATION DU PERSONNEL', 'parent' => '638'],
            ['numero' => '637.100', 'intitule' => 'REMUNERATIONS DU PERSON INTERIM', 'parent' => '638'],
            ['numero' => '641', 'intitule' => 'IMPOTS SUR SALAIRES'],
            ['numero' => '638.400', 'intitule' => 'MISSIONS', 'parent' => '641'],
            ['numero' => '661', 'intitule' => 'SALAIRES'],
            ['numero' => '647.000', 'intitule' => 'PENALITES ET AMENDES NON DEDUCT', 'parent' => '661'],
            ['numero' => '664', 'intitule' => 'COTISATIONS SOCIALES'],
            ['numero' => '662.600', 'intitule' => 'SUPPLT FAMILIAL PERS NON NATIONAL', 'parent' => '664'],
            ['numero' => '663.100', 'intitule' => 'INDEMNITES FORFAIT DE LOGEMENT', 'parent' => '664'],
            ['numero' => '664.110', 'intitule' => 'AF PERSONNEL NATIONAL (CSS)', 'parent' => '664'],
            ['numero' => '664.120', 'intitule' => 'AT PERSONNEL NATIONAL (CSS)', 'parent' => '664'],
            ['numero' => '664.130', 'intitule' => 'RETRAITE PERSONNEL NATION (IPRES)', 'parent' => '664'],
        ];

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
