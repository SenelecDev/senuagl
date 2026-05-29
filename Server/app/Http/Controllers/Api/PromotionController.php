<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Avancement;
use App\Models\GF;
use App\Models\NR;
use App\Models\Unite;
use App\Models\NoteAppreciation;
use App\Services\PromotionEligibilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    protected $eligibilityService;

    public function __construct(PromotionEligibilityService $eligibilityService)
    {
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Liste de priorité GF pour tous les agents
     */
    public function listePrioriteGFTous(Request $request)
    {
        $annee = (int) $request->query('annee', now()->year);
        $listePriorite = $this->eligibilityService->listePrioriteGFTous($annee);

        $data = $listePriorite->map(function ($item) {
            return [
                'matricule' => $item['agent']->matricule,
                'nom' => $item['agent']->nom,
                'prenom' => $item['agent']->prenom,
                'note' => $item['note'],
                'mention_note' => $this->eligibilityService->mentionNote($item['note']),
                'date_embauche' => $item['agent']->date_embauche,
                'anciennete_ans' => $item['agent']->anciennete,
                'gf_actuel' => $item['agent']->gfActuel?->id_gf,
                'derniere_promotion' => $item['derniere_promotion']?->date,
                'prochaine_promotion_annee' => $this->prochaineAnnee(
                    $item['derniere_promotion']?->date,
                    PromotionEligibilityService::DELAI_PROMOTION_GF_ANNEES
                ),
                'deja_promu' => $item['deja_promu'] ?? false,
                'agent' => $item['agent']->load(['poste', 'gfActuel', 'nrActuel'])
            ];
        });

        return response()->json([
            'annee' => $annee,
            'agents' => $data,
            'total' => count($data)
        ]);
    }

    /**
     * Liste de priorité NR pour tous les agents
     */
    public function listePrioriteNRTous(Request $request)
    {
        $annee = (int) $request->query('annee', now()->year);
        $listePriorite = $this->eligibilityService->listePrioriteNRTous($annee);

        $data = $listePriorite->map(function ($item) {
            return [
                'matricule' => $item['agent']->matricule,
                'nom' => $item['agent']->nom,
                'prenom' => $item['agent']->prenom,
                'note' => $item['note'],
                'mention_note' => $this->eligibilityService->mentionNote($item['note']),
                'date_embauche' => $item['agent']->date_embauche,
                'anciennete_ans' => $item['agent']->anciennete,
                'nr_actuel' => $item['agent']->nrActuel?->id_nr,
                'dernier_avancement' => $item['dernier_avancement']?->date,
                'prochain_avancement_annee' => $this->prochaineAnnee(
                    $item['dernier_avancement']?->date,
                    PromotionEligibilityService::DELAI_AVANCEMENT_NR_ANNEES
                ),
                'deja_avance' => $item['deja_avance'] ?? false,
                'agent' => $item['agent']->load(['poste', 'gfActuel', 'nrActuel'])
            ];
        });

        return response()->json([
            'annee' => $annee,
            'agents' => $data,
            'total' => count($data)
        ]);
    }

    /**
     * Liste de priorité GF pour une direction et une année
     */
    public function listePrioriteGF(Request $request, $directionId, $annee)
    {
        $direction = Unite::findOrFail($directionId);

        $listePriorite = $this->eligibilityService->listePrioriteGF($directionId, $annee);

        $data = $listePriorite->map(function ($item) {
            return [
                'matricule' => $item['agent']->matricule,
                'nom' => $item['agent']->nom,
                'prenom' => $item['agent']->prenom,
                'note' => $item['note'],
                'mention_note' => $this->eligibilityService->mentionNote($item['note']),
                'date_embauche' => $item['agent']->date_embauche,
                'anciennete_ans' => $item['agent']->anciennete,
                'gf_actuel' => $item['agent']->gfActuel?->id_gf,
                'derniere_promotion' => $item['derniere_promotion']?->date,
                'prochaine_promotion_annee' => $this->prochaineAnnee(
                    $item['derniere_promotion']?->date,
                    PromotionEligibilityService::DELAI_PROMOTION_GF_ANNEES
                ),
                'deja_promu' => $item['deja_promu'] ?? false,
                'agent' => $item['agent']->load(['poste', 'gfActuel', 'nrActuel'])
            ];
        });

        // Calcul des quotas et statistiques de la direction
        $totalAgentsInDirection = Agent::whereHas('poste', function ($query) use ($directionId) {
            $query->where('id_unite', $directionId);
        })->count();

        $alreadyPromotedGFCount = Avancement::whereNotNull('id_gf_nouveau')
            ->whereYear('date', $annee)
            ->whereHas('agent.poste', function ($query) use ($directionId) {
                $query->where('id_unite', $directionId);
            })->count();

        $quotaPercent = $this->quotaPercent($request, 15);

        return response()->json([
            'direction' => $direction,
            'annee' => $annee,
            'agents' => $data,
            'total' => count($data),
            'stats' => [
                'total_agents' => $totalAgentsInDirection,
                'already_promoted' => $alreadyPromotedGFCount,
                'quota_percent' => $quotaPercent,
                'quota_count' => (int) round($totalAgentsInDirection * ($quotaPercent / 100))
            ]
        ]);
    }

    /**
     * Liste de priorité NR pour une direction et une année
     */
    public function listePrioriteNR(Request $request, $directionId, $annee)
    {
        $direction = Unite::findOrFail($directionId);

        $listePriorite = $this->eligibilityService->listePrioriteNR($directionId, $annee);

        $data = $listePriorite->map(function ($item) {
            return [
                'matricule' => $item['agent']->matricule,
                'nom' => $item['agent']->nom,
                'prenom' => $item['agent']->prenom,
                'note' => $item['note'],
                'mention_note' => $this->eligibilityService->mentionNote($item['note']),
                'date_embauche' => $item['agent']->date_embauche,
                'anciennete_ans' => $item['agent']->anciennete,
                'nr_actuel' => $item['agent']->nrActuel?->id_nr,
                'dernier_avancement' => $item['dernier_avancement']?->date,
                'prochain_avancement_annee' => $this->prochaineAnnee(
                    $item['dernier_avancement']?->date,
                    PromotionEligibilityService::DELAI_AVANCEMENT_NR_ANNEES
                ),
                'deja_avance' => $item['deja_avance'] ?? false,
                'agent' => $item['agent']->load(['poste', 'gfActuel', 'nrActuel'])
            ];
        });

        // Calcul des quotas et statistiques de la direction
        $totalAgentsInDirection = Agent::whereHas('poste', function ($query) use ($directionId) {
            $query->where('id_unite', $directionId);
        })->count();

        $alreadyAdvancedNRCount = Avancement::whereNotNull('id_nr_nouveau')
            ->whereYear('date', $annee)
            ->whereHas('agent.poste', function ($query) use ($directionId) {
                $query->where('id_unite', $directionId);
            })->count();

        $quotaPercent = $this->quotaPercent($request, 35);

        return response()->json([
            'direction' => $direction,
            'annee' => $annee,
            'agents' => $data,
            'total' => count($data),
            'stats' => [
                'total_agents' => $totalAgentsInDirection,
                'already_advanced' => $alreadyAdvancedNRCount,
                'quota_percent' => $quotaPercent,
                'quota_count' => (int) round($totalAgentsInDirection * ($quotaPercent / 100))
            ]
        ]);
    }

    /**
     * Enregistrer une promotion GF
     */
    public function promouvoir(Request $request)
    {
        $validated = $request->validate([
            'matricule_agent' => 'required|exists:agents,matricule',
            'id_gf_nouveau' => 'required|exists:gfs,id_gf',
            'date' => 'required|date',
            'motif' => 'nullable|string'
        ]);

        $agent = Agent::findOrFail($validated['matricule_agent']);
        $gfAncien = $agent->gfActuel;

        if (!$gfAncien) {
            return response()->json(['error' => 'Impossible de déterminer le GF actuel de l\'agent.'], 422);
        }

        $gfNouveau = GF::findOrFail($validated['id_gf_nouveau']);

        if ($gfNouveau->ordre <= $gfAncien->ordre) {
            return response()->json(['error' => 'Le nouveau GF doit être supérieur à l\'ancien.'], 422);
        }

        $tubeMax = GF::find($agent->poste->tube_max);
        if (!$tubeMax || $gfNouveau->ordre > $tubeMax->ordre) {
            return response()->json(['error' => 'Le nouveau GF dépasse le plafond autorisé pour ce poste.'], 422);
        }

        $annee = Carbon::parse($validated['date'])->year;

        // 1. Vérifier la note d'appréciation pour l'année (dernière note avant l'année de promotion)
        $note = NoteAppreciation::where('matricule_agent', $agent->matricule)
            ->where('annee', '<', $annee)
            ->orderBy('annee', 'desc')
            ->first();
        if (!$note || $note->note <= PromotionEligibilityService::SEUIL_PROMOTION_GF) {
            return response()->json(['error' => 'L\'agent n\'a pas une note d\'appréciation suffisante (> 75) pour l\'année ' . $annee . '.'], 422);
        }

        // 2. Vérifier l'éligibilité via le service
        if (!$this->eligibilityService->estEligiblePromotionGF($agent, $annee)) {
            return response()->json(['error' => 'L\'agent n\'est pas éligible à une promotion GF pour l\'année ' . $annee . ' (délai de carence de 3 ans non respecté, déjà promu/avancé cette année, ou plafonné).'], 422);
        }

        $avancement = Avancement::create([
            'date' => $validated['date'],
            'motif' => $validated['motif'] ?? 'Promotion GF',
            'matricule_agent' => $validated['matricule_agent'],
            'id_gf_ancien' => $gfAncien->id_gf,
            'id_gf_nouveau' => $validated['id_gf_nouveau'],
            'id_nr_ancien' => $agent->id_nr_actuel,
            'id_nr_nouveau' => null
        ]);

        $agent->update(['id_gf_actuel' => $validated['id_gf_nouveau']]);

        return response()->json($avancement, 201);
    }

    /**
     * Enregistrer un avancement NR
     */
    public function avancer(Request $request)
    {
        $validated = $request->validate([
            'matricule_agent' => 'required|exists:agents,matricule',
            'id_nr_nouveau' => 'required|exists:nrs,id_nr',
            'date' => 'required|date',
            'motif' => 'nullable|string'
        ]);

        $agent = Agent::findOrFail($validated['matricule_agent']);
        $nrAncien = $agent->nrActuel;

        if (!$nrAncien) {
            return response()->json(['error' => 'Impossible de déterminer le NR actuel de l\'agent.'], 422);
        }

        $nrNouveau = NR::findOrFail($validated['id_nr_nouveau']);

        if ($nrNouveau->ordre <= $nrAncien->ordre) {
            return response()->json(['error' => 'Le nouveau NR doit être supérieur à l\'ancien.'], 422);
        }

        $annee = Carbon::parse($validated['date'])->year;

        // 1. Vérifier la note d'appréciation pour l'année (dernière note avant l'année d'avancement)
        $note = NoteAppreciation::where('matricule_agent', $agent->matricule)
            ->where('annee', '<', $annee)
            ->orderBy('annee', 'desc')
            ->first();
        if (!$note || $note->note <= PromotionEligibilityService::SEUIL_AVANCEMENT_NR) {
            return response()->json(['error' => 'L\'agent n\'a pas une note d\'appréciation suffisante (> 50) pour l\'année ' . $annee . '.'], 422);
        }

        // 2. Vérifier l'éligibilité via le service
        if (!$this->eligibilityService->estEligibleAvancementNR($agent, $annee)) {
            return response()->json(['error' => 'L\'agent n\'est pas éligible à un avancement NR pour l\'année ' . $annee . ' (délai de carence de 2 ans non respecté ou déjà promu/avancé cette année).'], 422);
        }

        $avancement = Avancement::create([
            'date' => $validated['date'],
            'motif' => $validated['motif'] ?? 'Avancement NR',
            'matricule_agent' => $validated['matricule_agent'],
            'id_gf_ancien' => $agent->id_gf_actuel,
            'id_gf_nouveau' => null,
            'id_nr_ancien' => $nrAncien->id_nr,
            'id_nr_nouveau' => $validated['id_nr_nouveau']
        ]);

        $agent->update(['id_nr_actuel' => $validated['id_nr_nouveau']]);

        return response()->json($avancement, 201);
    }

    private function quotaPercent(Request $request, int $default): float
    {
        $quota = $request->query('quota_percent', $default);

        if (!is_numeric($quota)) {
            return $default;
        }

        return max(0, min(100, (float) $quota));
    }

    private function prochaineAnnee($date, int $delaiAnnees): ?int
    {
        if (!$date) {
            return null;
        }

        return Carbon::parse($date)->addYears($delaiAnnees)->year;
    }
}
