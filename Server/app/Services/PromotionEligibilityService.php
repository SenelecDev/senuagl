<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Avancement;
use App\Models\NoteAppreciation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PromotionEligibilityService
{
    public const SEUIL_PROMOTION_GF = 75;
    public const SEUIL_AVANCEMENT_NR = 50;
    public const DELAI_PROMOTION_GF_ANNEES = 3;
    public const DELAI_AVANCEMENT_NR_ANNEES = 2;

    /**
     * Liste de tous les agents éligibles pour une promotion GF
     */
    public function listePrioriteGFTous($annee): Collection
    {
        $agents = Agent::with(['gfActuel', 'poste', 'nrActuel'])->get();

        $eligible = [];

        foreach ($agents as $agent) {
            $note = $this->getNoteAppreciation($agent->matricule, $annee);
            if ($note === null || $note <= self::SEUIL_PROMOTION_GF) {
                continue;
            }

            // Check if already promoted GF in this year
            $alreadyPromotedGF = Avancement::where('matricule_agent', $agent->matricule)
                ->whereYear('date', $annee)
                ->whereNotNull('id_gf_nouveau')
                ->exists();

            if ($alreadyPromotedGF) {
                $eligible[] = [
                    'agent' => $agent,
                    'note' => $note,
                    'deja_promu' => true,
                    'date_embauche' => $agent->date_embauche,
                    'derniere_promotion' => $this->getDernierePromotionGF($agent->matricule),
                ];
                continue;
            }

            if (!$this->estEligiblePromotionGF($agent, $annee)) {
                continue;
            }

            $eligible[] = [
                'agent' => $agent,
                'note' => $note,
                'deja_promu' => false,
                'date_embauche' => $agent->date_embauche,
                'derniere_promotion' => $this->getDernierePromotionGF($agent->matricule),
            ];
        }

        return collect($eligible)->sort(function ($a, $b) {
            if ($a['deja_promu'] !== $b['deja_promu']) {
                return $a['deja_promu'] <=> $b['deja_promu'];
            }
            if ($a['deja_promu']) {
                return $a['derniere_promotion']?->date <=> $b['derniere_promotion']?->date;
            }
            if ($a['note'] !== $b['note']) {
                return $b['note'] <=> $a['note'];
            }
            $dateA = $a['derniere_promotion']?->date ?? $a['agent']->date_embauche;
            $dateB = $b['derniere_promotion']?->date ?? $b['agent']->date_embauche;
            return $dateA <=> $dateB;
        })->values();
    }

    /**
     * Liste de tous les agents éligibles pour un avancement NR
     */
    public function listePrioriteNRTous($annee): Collection
    {
        $agents = Agent::with(['gfActuel', 'poste', 'nrActuel'])->get();

        $eligible = [];

        foreach ($agents as $agent) {
            $note = $this->getNoteAppreciation($agent->matricule, $annee);
            if ($note === null || $note <= self::SEUIL_AVANCEMENT_NR) {
                continue;
            }

            // Check if already advanced NR in this year
            $alreadyAdvancedNR = Avancement::where('matricule_agent', $agent->matricule)
                ->whereYear('date', $annee)
                ->whereNotNull('id_nr_nouveau')
                ->exists();

            if ($alreadyAdvancedNR) {
                $eligible[] = [
                    'agent' => $agent,
                    'note' => $note,
                    'deja_avance' => true,
                    'date_embauche' => $agent->date_embauche,
                    'dernier_avancement' => $this->getDernierAvancementNR($agent->matricule),
                ];
                continue;
            }

            if (!$this->estEligibleAvancementNR($agent, $annee)) {
                continue;
            }

            $eligible[] = [
                'agent' => $agent,
                'note' => $note,
                'deja_avance' => false,
                'date_embauche' => $agent->date_embauche,
                'dernier_avancement' => $this->getDernierAvancementNR($agent->matricule),
            ];
        }

        return collect($eligible)->sort(function ($a, $b) {
            if ($a['deja_avance'] !== $b['deja_avance']) {
                return $a['deja_avance'] <=> $b['deja_avance'];
            }
            if ($a['deja_avance']) {
                return $a['dernier_avancement']?->date <=> $b['dernier_avancement']?->date;
            }
            if ($a['note'] !== $b['note']) {
                return $b['note'] <=> $a['note'];
            }
            $dateA = $a['dernier_avancement']?->date ?? $a['agent']->date_embauche;
            $dateB = $b['dernier_avancement']?->date ?? $b['agent']->date_embauche;
            return $dateA <=> $dateB;
        })->values();
    }

    /**
     * Liste des agents éligibles pour une promotion GF
     * Respecte: note > 75, délai 3 ans, tri par mérite puis ancienneté.
     */
    public function listePrioriteGF($directionId, $annee): Collection
    {
        $agents = Agent::whereHas('poste', function ($query) use ($directionId) {
            $query->where('id_unite', $directionId);
        })
        ->with(['gfActuel', 'poste', 'nrActuel'])
        ->get();

        $eligible = [];

        foreach ($agents as $agent) {
            $note = $this->getNoteAppreciation($agent->matricule, $annee);
            if ($note === null || $note <= self::SEUIL_PROMOTION_GF) {
                continue;
            }

            // Check if already promoted GF in this year
            $alreadyPromotedGF = Avancement::where('matricule_agent', $agent->matricule)
                ->whereYear('date', $annee)
                ->whereNotNull('id_gf_nouveau')
                ->exists();

            if ($alreadyPromotedGF) {
                $eligible[] = [
                    'agent' => $agent,
                    'note' => $note,
                    'deja_promu' => true,
                    'date_embauche' => $agent->date_embauche,
                    'derniere_promotion' => $this->getDernierePromotionGF($agent->matricule),
                ];
                continue;
            }

            if (!$this->estEligiblePromotionGF($agent, $annee)) {
                continue;
            }

            $eligible[] = [
                'agent' => $agent,
                'note' => $note,
                'deja_promu' => false,
                'date_embauche' => $agent->date_embauche,
                'derniere_promotion' => $this->getDernierePromotionGF($agent->matricule),
            ];
        }

        return collect($eligible)->sort(function ($a, $b) {
            if ($a['deja_promu'] !== $b['deja_promu']) {
                return $a['deja_promu'] <=> $b['deja_promu'];
            }
            if ($a['deja_promu']) {
                return $a['derniere_promotion']?->date <=> $b['derniere_promotion']?->date;
            }
            if ($a['note'] !== $b['note']) {
                return $b['note'] <=> $a['note'];
            }
            $dateA = $a['derniere_promotion']?->date ?? $a['agent']->date_embauche;
            $dateB = $b['derniere_promotion']?->date ?? $b['agent']->date_embauche;
            return $dateA <=> $dateB;
        })->values();
    }

    /**
     * Liste des agents éligibles pour un avancement NR
     * Respecte: note > 50, délai 2 ans, tri par mérite puis ancienneté.
     */
    public function listePrioriteNR($directionId, $annee): Collection
    {
        $agents = Agent::whereHas('poste', function ($query) use ($directionId) {
            $query->where('id_unite', $directionId);
        })
        ->with(['gfActuel', 'poste', 'nrActuel'])
        ->get();

        $eligible = [];

        foreach ($agents as $agent) {
            $note = $this->getNoteAppreciation($agent->matricule, $annee);
            if ($note === null || $note <= self::SEUIL_AVANCEMENT_NR) {
                continue;
            }

            // Check if already advanced NR in this year
            $alreadyAdvancedNR = Avancement::where('matricule_agent', $agent->matricule)
                ->whereYear('date', $annee)
                ->whereNotNull('id_nr_nouveau')
                ->exists();

            if ($alreadyAdvancedNR) {
                $eligible[] = [
                    'agent' => $agent,
                    'note' => $note,
                    'deja_avance' => true,
                    'date_embauche' => $agent->date_embauche,
                    'dernier_avancement' => $this->getDernierAvancementNR($agent->matricule),
                ];
                continue;
            }

            if (!$this->estEligibleAvancementNR($agent, $annee)) {
                continue;
            }

            $eligible[] = [
                'agent' => $agent,
                'note' => $note,
                'deja_avance' => false,
                'date_embauche' => $agent->date_embauche,
                'dernier_avancement' => $this->getDernierAvancementNR($agent->matricule),
            ];
        }

        return collect($eligible)->sort(function ($a, $b) {
            if ($a['deja_avance'] !== $b['deja_avance']) {
                return $a['deja_avance'] <=> $b['deja_avance'];
            }
            if ($a['deja_avance']) {
                return $a['dernier_avancement']?->date <=> $b['dernier_avancement']?->date;
            }
            if ($a['note'] !== $b['note']) {
                return $b['note'] <=> $a['note'];
            }
            $dateA = $a['dernier_avancement']?->date ?? $a['agent']->date_embauche;
            $dateB = $b['dernier_avancement']?->date ?? $b['agent']->date_embauche;
            return $dateA <=> $dateB;
        })->values();
    }

    /**
     * Vérifie si un agent peut être promu GF
     */
    public function estEligiblePromotionGF(Agent $agent, $annee): bool
    {
        if (!$agent->poste || !$agent->gfActuel) {
            return false;
        }

        if ($agent->est_plafonne) {
            return false;
        }

        // Vérifier si l'agent a déjà reçu un avancement (GF ou NR) cette année
        $hasAvancementInYear = Avancement::where('matricule_agent', $agent->matricule)
            ->whereYear('date', $annee)
            ->exists();
        if ($hasAvancementInYear) {
            return false;
        }

        $dernierePromotion = $this->getDernierePromotionGF($agent->matricule);
        // Délai depuis la dernière promotion GF, ou depuis l'embauche si aucune promotion.
        $dateMin = Carbon::create($annee, 12, 31)->subYears(self::DELAI_PROMOTION_GF_ANNEES);

        if ($dernierePromotion) {
            return Carbon::parse($dernierePromotion->date) <= $dateMin;
        }

        if (!$agent->date_embauche) {
            return false;
        }

        $dateEmbauche = Carbon::parse($agent->date_embauche);
        return $dateEmbauche <= $dateMin;
    }

    /**
     * Vérifie si un agent peut être avancé NR
     */
    public function estEligibleAvancementNR(Agent $agent, $annee): bool
    {
        if (!$agent->nrActuel) {
            return false;
        }

        // Vérifier si l'agent a déjà reçu un avancement (GF ou NR) cette année
        $hasAvancementInYear = Avancement::where('matricule_agent', $agent->matricule)
            ->whereYear('date', $annee)
            ->exists();
        if ($hasAvancementInYear) {
            return false;
        }

        $dernierAvancement = $this->getDernierAvancementNR($agent->matricule);
        // Délai depuis le dernier avancement NR, ou depuis l'embauche si aucun avancement.
        $dateMin = Carbon::create($annee, 12, 31)->subYears(self::DELAI_AVANCEMENT_NR_ANNEES);

        if ($dernierAvancement) {
            return Carbon::parse($dernierAvancement->date) <= $dateMin;
        }

        if (!$agent->date_embauche) {
            return false;
        }

        $dateEmbauche = Carbon::parse($agent->date_embauche);
        return $dateEmbauche <= $dateMin;
    }

    /**
     * Récupère la dernière promotion GF d'un agent
     */
    private function getDernierePromotionGF($matriculeAgent): ?Avancement
    {
        return Avancement::where('matricule_agent', $matriculeAgent)
            ->whereNotNull('id_gf_nouveau')
            ->orderBy('date', 'desc')
            ->first();
    }

    /**
     * Récupère le dernier avancement NR d'un agent
     */
    private function getDernierAvancementNR($matriculeAgent): ?Avancement
    {
        return Avancement::where('matricule_agent', $matriculeAgent)
            ->whereNotNull('id_nr_nouveau')
            ->orderBy('date', 'desc')
            ->first();
    }

    /**
     * Récupère la note d'appréciation d'un agent pour une année donnée
     */
    private function getNoteAppreciation($matriculeAgent, $annee): ?int
    {
        $note = NoteAppreciation::where('matricule_agent', $matriculeAgent)
            ->where('annee', $annee)
            ->first();

        return $note?->note;
    }

    public function mentionNote(?int $note): ?string
    {
        if ($note === null) {
            return null;
        }

        if ($note <= 25) {
            return 'Insuffisant';
        }

        if ($note <= 50) {
            return 'Moyen';
        }

        if ($note <= 75) {
            return 'Satisfaisant';
        }

        return 'Très satisfaisant';
    }
}
