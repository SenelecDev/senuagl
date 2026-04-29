<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniteSeeder extends Seeder
{
    public function run()
    {
        // UniteSeeder (version corrigée avec les services)
    DB::table('unites')->insert([
        // DSI
        ['id_unite' => 'U01', 'nom' => 'Direction des Systèmes d\'Information', 'type' => 'Direction', 'id_parent' => null],

        // Départements (niveau 1)
        ['id_unite' => 'U02', 'nom' => 'Département Développement et Exploitation des Services (DDES)', 'type' => 'Departement', 'id_parent' => 'U01'],
        ['id_unite' => 'U03', 'nom' => 'Département Systèmes et Réseaux (DSR)', 'type' => 'Departement', 'id_parent' => 'U01'],

        // Autres structures rattachées directement à la DSI
        ['id_unite' => 'U04', 'nom' => 'Cellule de Coordination des Applications Métiers', 'type' => 'Cellule', 'id_parent' => 'U01'],
        ['id_unite' => 'U05', 'nom' => 'Service Projets, Urbanisation et Sécurité (SPUS)', 'type' => 'Service', 'id_parent' => 'U01'],
        ['id_unite' => 'U06', 'nom' => 'Service Archive et Documentation (SAD)', 'type' => 'Service', 'id_parent' => 'U01'],
        ['id_unite' => 'U07', 'nom' => 'Unité Administration, Gestion et Logistique (UAGL)', 'type' => 'Unite', 'id_parent' => 'U01'],

        // === Services du DSR ===
        ['id_unite' => 'U08', 'nom' => 'Service Supervision, Helpdesk et Support', 'type' => 'Service', 'id_parent' => 'U03'],
        ['id_unite' => 'U09', 'nom' => 'Service Infrastructures Systèmes', 'type' => 'Service', 'id_parent' => 'U03'],
        ['id_unite' => 'U10', 'nom' => 'Service Infrastructures Réseaux', 'type' => 'Service', 'id_parent' => 'U03'],

        // === Services du DDES ===
        ['id_unite' => 'U11', 'nom' => 'Service Production et Gestion des Données', 'type' => 'Service', 'id_parent' => 'U02'],

        // Services Métiers (CSM)
        ['id_unite' => 'U12', 'nom' => 'Service Métier Commercial', 'type' => 'Service', 'id_parent' => 'U02'],
        ['id_unite' => 'U13', 'nom' => 'Service Métier Technique', 'type' => 'Service', 'id_parent' => 'U02'],
        ['id_unite' => 'U14', 'nom' => 'Service Métier Finances', 'type' => 'Service', 'id_parent' => 'U02'],
        ['id_unite' => 'U15', 'nom' => 'Service Métier Comptabilité-RH-Logistique', 'type' => 'Service', 'id_parent' => 'U02'],
    ]);
    }
}
