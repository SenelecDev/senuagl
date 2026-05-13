<?php

namespace Database\Seeders\Budget;

use App\Models\Budget\Compte;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            ['numero' => '605100', 'intitule' => 'EAU'],
            ['numero' => '605200', 'intitule' => 'ELECTRICITE'],
            ['numero' => '605300', 'intitule' => 'AUTRES ENERGIES (Carburant)'],
            ['numero' => '605500', 'intitule' => 'FOURNIT DE BUREAU NON STOCKABLE'],
            ['numero' => '605600', 'intitule' => 'PETIT MATERIEL ET OUTILLAGE'],
            ['numero' => '605800', 'intitule' => 'TRAVAUX ET FRAIS DIVERS'],
            ['numero' => '605', 'intitule' => 'EAU/ELECTRICITE/GAZ/SERVICES ET ACHATS DIVERS'],
            ['numero' => '614000', 'intitule' => 'TRANSPORTS DU PERSONNEL'],
            ['numero' => '618000', 'intitule' => 'VOYAGES ET DEPLACEMENTS'],
            ['numero' => '61.', 'intitule' => 'TRANSPORTS'],
            ['numero' => '622200', 'intitule' => 'LOCATION DE BATIMENTS'],
            ['numero' => '622800', 'intitule' => 'LOCATIONS ET CHARG LOC DIVERSES'],
            ['numero' => '622', 'intitule' => 'LOCATIONS'],
            ['numero' => '624100', 'intitule' => 'ENTRETIEN ET REPARAT BIENS IMMOB'],
            ['numero' => '624200', 'intitule' => 'ENTRETIEN ET REPARAT BIENS MOBIL'],
            ['numero' => '624800', 'intitule' => 'AUTRES ENTRETIENS ET REPARATIONS'],
            ['numero' => '624', 'intitule' => 'ENTRETIENS ET REPARATIONS'],
            ['numero' => '625.200', 'intitule' => 'ASSURANCES MATERIEL DE TRANSPT'],
            ['numero' => '626.500', 'intitule' => 'DOCUMENTATION GENERALE'],
            ['numero' => '627.600', 'intitule' => 'CADEAUX ET DONS'],
            ['numero' => '628.000', 'intitule' => 'FRAIS DE TELECOMMUNICATIONS'],
            ['numero' => '631.100', 'intitule' => 'FRAIS BANCAIRES'],
            ['numero' => '632.400', 'intitule' => 'HONORAIRES'],
            ['numero' => '633.000', 'intitule' => 'FRAIS DE FORMATION DU PERSONNEL'],
            ['numero' => '637.100', 'intitule' => 'REMUNERATIONS DU PERSON INTERIM'],
            ['numero' => '638', 'intitule' => 'FRAIS LIES A LA RESTAURATION, MISSIONS ET RECEPTIONS'],
            ['numero' => '638.400', 'intitule' => 'MISSIONS'],
            ['numero' => '641', 'intitule' => 'IMPOTS SUR SALAIRES'],
            ['numero' => '647.000', 'intitule' => 'PENALITES ET AMENDES NON DEDUCT'],
            ['numero' => '661', 'intitule' => 'SALAIRES'],
            ['numero' => '662.600', 'intitule' => 'SUPPLT FAMILIAL PERS NON NATIONAL'],
            ['numero' => '663.100', 'intitule' => 'INDEMNITES FORFAIT DE LOGEMENT'],
            ['numero' => '664.110', 'intitule' => 'AF PERSONNEL NATIONAL (CSS)'],
            ['numero' => '664.120', 'intitule' => 'AT PERSONNEL NATIONAL (CSS)'],
            ['numero' => '664.130', 'intitule' => 'RETRAITE PERSONNEL NATION (IPRES)'],
            ['numero' => '664', 'intitule' => 'COTISATIONS SOCIALES'],
        ];

        foreach ($comptes as $compte) {
            Compte::query()->updateOrCreate(
                ['numero' => $compte['numero']],
                ['intitule' => $compte['intitule']],
            );
        }
    }
}
