<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Avancement;
use App\Models\GF;
use App\Models\NoteAppreciation;
use App\Models\NR;
use App\Services\PromotionEligibilityService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PromotionAndAvancementSeeder extends Seeder
{
    public function run()
    {
        $agents = Agent::with(['poste', 'gfActuel', 'nrActuel'])->get();

        foreach ($agents as $agent) {
            if (!$agent->poste || !$agent->gfActuel || !$agent->nrActuel) {
                continue;
            }

            // Créer des promotions GF basées sur les notes
            $this->createPromotionsGF($agent);

            // Créer des avancements NR basés sur les notes
            $this->createAvancementsNR($agent);
        }
    }

    private function createPromotionsGF(Agent $agent): void
    {
        $poste = $agent->poste;
        $gfMin = GF::find($poste->tube_min);
        $gfMax = GF::find($poste->tube_max);
        $gfActuel = $agent->gfActuel;

        if (!$gfMin || !$gfMax || $gfActuel->ordre >= $gfMax->ordre) {
            return;
        }

        $notes = NoteAppreciation::where('matricule_agent', $agent->matricule)
            ->where('note', '>', 75)
            ->orderBy('annee')
            ->get();

        $gfCourant = $gfActuel;
        $lastPromotionDate = null;

        foreach ($notes as $note) {
            $promotionDate = Carbon::create($note->annee, 1, 1)->addMonths(5);

            // Vérifier le délai entre deux promotions GF.
            if (
                $lastPromotionDate
                && Carbon::parse($lastPromotionDate)->addYears(PromotionEligibilityService::DELAI_PROMOTION_GF_ANNEES)->gt($promotionDate)
            ) {
                continue;
            }

            // Vérifier qu'on n'a pas atteint le plafond
            if ($gfCourant->ordre >= $gfMax->ordre) {
                break;
            }

            // Déterminer le prochain GF (progression de 1 niveau généralement)
            $nextGfOrdre = min($gfCourant->ordre + 1, $gfMax->ordre);
            $gfNouveau = GF::where('ordre', $nextGfOrdre)->first();

            if (!$gfNouveau || $gfNouveau->ordre <= $gfCourant->ordre) {
                continue;
            }

            Avancement::create([
                'date' => $promotionDate->format('Y-m-d'),
                'motif' => 'Promotion GF (Note: ' . $note->note . '/100)',
                'matricule_agent' => $agent->matricule,
                'id_gf_ancien' => $gfCourant->id_gf,
                'id_gf_nouveau' => $gfNouveau->id_gf,
                'id_nr_ancien' => $agent->id_nr_actuel,
                'id_nr_nouveau' => null,
            ]);

            $gfCourant = $gfNouveau;
            $lastPromotionDate = $promotionDate->format('Y-m-d');
        }

        // Mettre à jour le GF actuel de l'agent si changement
        if ($gfCourant->id_gf !== $agent->id_gf_actuel) {
            $agent->update(['id_gf_actuel' => $gfCourant->id_gf]);
        }
    }

    private function createAvancementsNR(Agent $agent): void
    {
        $nrActuel = $agent->nrActuel;
        $allNRs = NR::orderBy('ordre')->get();
        $nrMax = $allNRs->last();

        if (!$nrMax || $nrActuel->ordre >= $nrMax->ordre) {
            return;
        }

        $notes = NoteAppreciation::where('matricule_agent', $agent->matricule)
            ->where('note', '>', 50)
            ->orderBy('annee')
            ->get();

        $nrCourant = $nrActuel;
        $lastAvancementDate = null;

        foreach ($notes as $note) {
            $avancementDate = Carbon::create($note->annee, 1, 1)->addMonths(8);

            // Vérifier le délai entre deux avancements NR.
            if (
                $lastAvancementDate
                && Carbon::parse($lastAvancementDate)->addYears(PromotionEligibilityService::DELAI_AVANCEMENT_NR_ANNEES)->gt($avancementDate)
            ) {
                continue;
            }

            // Vérifier qu'on n'a pas atteint le plafond
            if ($nrCourant->ordre >= $nrMax->ordre) {
                break;
            }

            // Déterminer le prochain NR (progression de 1 niveau généralement)
            $nextNROrdre = min($nrCourant->ordre + 1, $nrMax->ordre);
            $nrNouveau = NR::where('ordre', $nextNROrdre)->first();

            if (!$nrNouveau || $nrNouveau->ordre <= $nrCourant->ordre) {
                continue;
            }

            // Vérifier qu'il n'existe pas déjà un avancement pour cet agent et cette année
            $existant = Avancement::where('matricule_agent', $agent->matricule)
                ->where('id_nr_nouveau', $nrNouveau->id_nr)
                ->exists();

            if ($existant) {
                continue;
            }

            Avancement::create([
                'date' => $avancementDate->format('Y-m-d'),
                'motif' => 'Avancement NR (Note: ' . $note->note . '/100)',
                'matricule_agent' => $agent->matricule,
                'id_gf_ancien' => $agent->id_gf_actuel,
                'id_gf_nouveau' => null,
                'id_nr_ancien' => $nrCourant->id_nr,
                'id_nr_nouveau' => $nrNouveau->id_nr,
            ]);

            $nrCourant = $nrNouveau;
            $lastAvancementDate = $avancementDate->format('Y-m-d');
        }

        // Mettre à jour le NR actuel de l'agent si changement
        if ($nrCourant->id_nr !== $agent->id_nr_actuel) {
            $agent->update(['id_nr_actuel' => $nrCourant->id_nr]);
        }
    }
}
