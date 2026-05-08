<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateAgentLocationsSeeder extends Seeder
{
    public function run()
    {
        // Liste des villes pour le nouveau lieu de naissance
        $villesNaissance = [
            'Dakar', 'Pikine', 'Guédiawaye', 'Rufisque', 'Thiès', 'Mbour', 'Kaolack',
            'Saint-Louis', 'Touba', 'Diourbel', 'Louga', 'Tambacounda', 'Ziguinchor',
            'Kolda', 'Fatick', 'Kédougou', 'Sédhiou', 'Matam', 'Bignona', 'Nioro',
            'Vélingara', 'Ouakam', 'Grand Yoff', 'Mboro', 'Koungheul', 'Kaffrine'
        ];

        // Récupérer tous les agents
        $agents = DB::table('agents')->get();

        foreach ($agents as $agent) {
            // L'ancienne valeur de lieu_naissance (actuellement stockée dans la colonne lieu_naissance)
            // va devenir le lieu de fonction (colonne 'lieu')
            $ancienLieu = $agent->lieu_naissance;

            // Générer une nouvelle ville aléatoire
            $nouveauLieuNaissance = $villesNaissance[array_rand($villesNaissance)];

            DB::table('agents')
                ->where('matricule', $agent->matricule)
                ->update([
                    'lieu' => $ancienLieu,               // colonne 'lieu' = ancien lieu_naissance
                    'lieu_naissance' => $nouveauLieuNaissance,
                ]);
        }
    }
}