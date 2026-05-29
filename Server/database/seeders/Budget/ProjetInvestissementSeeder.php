<?php

namespace Database\Seeders\Budget;

use App\Models\Budget\ProjetInvestissement;
use Illuminate\Database\Seeder;

class ProjetInvestissementSeeder extends Seeder
{
    public function run(): void
    {
        $projets = [
            ['code_projet' => '21IX10013889', 'libelle' => 'Travaux de cablage des sites de Senelec', 'bailleur' => 'FONDS PROPRES', 'cr' => '64162', 'montant_marche' => 300, 'cout_projet' => 300, 'fp_annee' => 196, 'fe_annee' => 0],
            ['code_projet' => '22AX10214016', 'libelle' => 'Extension de la GED(GED technique)', 'bailleur' => 'FONDS PROPRES', 'cr' => '64158', 'montant_marche' => 100, 'cout_projet' => 100, 'fp_annee' => 69, 'fe_annee' => 0],
            ['code_projet' => '22IE10014075', 'libelle' => 'Extension de la GMAO à la TAG4 et Consolidation des environnements techniques', 'bailleur' => 'FONDS PROPRES', 'cr' => null, 'montant_marche' => 65, 'cout_projet' => 65, 'fp_annee' => 20, 'fe_annee' => 0],
            ['code_projet' => '22II10014072', 'libelle' => 'Acquisition d\'équipements de Synchronisation des Réseaux BLR du RCVD', 'bailleur' => 'FONDS PROPRES', 'cr' => '64028', 'montant_marche' => 175, 'cout_projet' => 175, 'fp_annee' => 19, 'fe_annee' => 0],
            ['code_projet' => '22II10014073', 'libelle' => 'Accompagnement pour la Refonte du LAN, MAN, WAN et Implémentation de la QOS dans le Réseau Informatique de Senelec', 'bailleur' => 'FONDS PROPRES', 'cr' => '64245', 'montant_marche' => 200, 'cout_projet' => 200, 'fp_annee' => 75, 'fe_annee' => 0],
            ['code_projet' => '22II10014077', 'libelle' => 'Acquisitions des pieces de Rechanges pour le parc des onduleurs et Régulateurs', 'bailleur' => 'FONDS PROPRES', 'cr' => '64175', 'montant_marche' => 20, 'cout_projet' => 20, 'fp_annee' => 20, 'fe_annee' => 0],
            ['code_projet' => '22IX10014082', 'libelle' => 'Transformation du Système d\'Information Oracle : Acquisition nouvelle infrastructure Oracle (ERP et Consolidation des Bases de Données)', 'bailleur' => 'FONDS PROPRES', 'cr' => null, 'montant_marche' => 550, 'cout_projet' => 750, 'fp_annee' => 550, 'fe_annee' => 0],
            ['code_projet' => '23IX10015077', 'libelle' => 'Gestion des pièces de rechanges dans GMAO', 'bailleur' => 'FONDS PROPRES', 'cr' => null, 'montant_marche' => 300, 'cout_projet' => 300, 'fp_annee' => 120, 'fe_annee' => 0],
            ['code_projet' => '23IX10015081', 'libelle' => 'Renforcement de la connectivité par raccordement au réseau fibre optique des nouveaux site de SENELEC', 'bailleur' => 'FONDS PROPRES', 'cr' => '64189', 'montant_marche' => 400, 'cout_projet' => 400, 'fp_annee' => 200, 'fe_annee' => 0],
            ['code_projet' => '24II10015086', 'libelle' => 'Système Standard de gestion des compteurs intelligents', 'bailleur' => 'FONDS PROPRES', 'cr' => null, 'montant_marche' => 3304, 'cout_projet' => 3304, 'fp_annee' => 3304, 'fe_annee' => 0],
            ['code_projet' => '24II10015087', 'libelle' => 'Mise en place d\'un Cluster de Haute disponibilité sur le site principal + une réplication sur le Site de replis', 'bailleur' => 'FONDS PROPRES', 'cr' => null, 'montant_marche' => 2968, 'cout_projet' => 2968, 'fp_annee' => 2968, 'fe_annee' => 0],
            ['code_projet' => '25QJ10015128', 'libelle' => 'Migration Oracle', 'bailleur' => 'FONDS PROPRES', 'cr' => null, 'montant_marche' => 1500, 'cout_projet' => 1500, 'fp_annee' => 1300, 'fe_annee' => 0],
        ];

        foreach ($projets as $projet) {
            ProjetInvestissement::query()->updateOrCreate(
                ['code_projet' => $projet['code_projet'], 'annee' => 2026],
                [
                    'libelle' => $projet['libelle'],
                    'bailleur' => $projet['bailleur'],
                    'cr' => $projet['cr'],
                    'montant_marche' => $projet['montant_marche'] * 1000000, // Les montants sont souvent en millions dans ces tableaux Excel
                    'cout_projet' => $projet['cout_projet'] * 1000000,
                    'fp_annee' => $projet['fp_annee'] * 1000000,
                    'fe_annee' => $projet['fe_annee'] * 1000000,
                ]
            );
        }
    }
}
