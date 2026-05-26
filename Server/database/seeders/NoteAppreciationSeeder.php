<?php

namespace Database\Seeders;

use App\Models\NoteAppreciation;
use App\Models\Agent;
use Illuminate\Database\Seeder;

class NoteAppreciationSeeder extends Seeder
{
    public function run()
    {
        $agents = Agent::all();
        $annees = [2023, 2024, 2025, 2026];

        foreach ($agents as $agent) {
            foreach ($annees as $annee) {
                // Générer une note basée sur une distribution normale autour de 70
                $note = (int) max(0, min(100, random_int(45, 95)));

                NoteAppreciation::create([
                    'matricule_agent' => $agent->matricule,
                    'annee' => $annee,
                    'note' => $note,
                    'commentaire' => $this->getCommentaire($note)
                ]);
            }
        }
    }

    private function getCommentaire($note): string
    {
        if ($note >= 90) {
            return 'Excellent rendement, très satisfait.';
        } elseif ($note >= 80) {
            return 'Bon rendement général, satisfait.';
        } elseif ($note >= 70) {
            return 'Rendement satisfaisant.';
        } elseif ($note >= 60) {
            return 'Rendement acceptable mais peut s\'améliorer.';
        } else {
            return 'Rendement à améliorer, suivi recommandé.';
        }
    }
}
