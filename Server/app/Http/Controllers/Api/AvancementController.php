<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Avancement;
use App\Models\Agent;
use App\Models\GF;
use App\Models\NR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AvancementController extends Controller
{
    /**
     * Liste des avancements (avec filtres optionnels)
     */
    public function index(Request $request)
    {
        $query = Avancement::with(['agent', 'gfAncien', 'gfNouveau', 'nrAncien', 'nrNouveau']);

        if ($request->has('agent')) {
            $query->where('matricule_agent', $request->agent);
        }

        if ($request->has('type')) {
            if ($request->type === 'GF') {
                $query->whereNotNull('id_gf_nouveau');
            } elseif ($request->type === 'NR') {
                $query->whereNotNull('id_nr_nouveau');
            }
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $avancements = $query->orderBy('date', 'desc')->paginate($perPage);

        return response()->json($avancements);
    }

    /**
     * Détail d’un avancement
     */
    public function show($id)
    {
        $avancement = Avancement::with(['agent', 'gfAncien', 'gfNouveau', 'nrAncien', 'nrNouveau'])
            ->findOrFail($id);

        return response()->json($avancement);
    }

    /**
     * Liste des avancements d’un agent donné
     */
    public function getByAgent($matricule)
    {
        $agent = Agent::findOrFail($matricule);

        $avancements = Avancement::where('matricule_agent', $matricule)
            ->with(['gfAncien', 'gfNouveau', 'nrAncien', 'nrNouveau'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'agent' => $agent,
            'avancements' => $avancements,
        ]);
    }

    /**
     * Créer un nouvel avancement (promotion GF et/ou changement NR)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'matricule_agent' => 'required|exists:agents,matricule',
            'id_gf_ancien' => 'nullable|exists:gfs,id_gf',
            'id_gf_nouveau' => 'nullable|exists:gfs,id_gf',
            'id_nr_ancien' => 'nullable|exists:nrs,id_nr',
            'id_nr_nouveau' => 'nullable|exists:nrs,id_nr',
        ]);

        $agent = Agent::findOrFail($validated['matricule_agent']);

        // Vérification : au moins un changement
        if (empty($validated['id_gf_nouveau']) && empty($validated['id_nr_nouveau'])) {
            return response()->json(['error' => 'Au moins un changement (GF ou NR) doit être spécifié.'], 422);
        }

        // Règles pour le GF
        if (!empty($validated['id_gf_nouveau'])) {
            // Ancien GF obligatoire si nouveau GF fourni
            if (empty($validated['id_gf_ancien'])) {
                return response()->json(['error' => 'id_gf_ancien est requis quand id_gf_nouveau est fourni.'], 422);
            }

            $gfAncien = GF::find($validated['id_gf_ancien']);
            $gfNouveau = GF::find($validated['id_gf_nouveau']);

            // Vérifier progression (ordre strictement supérieur)
            if ($gfNouveau->ordre <= $gfAncien->ordre) {
                return response()->json(['error' => 'Le nouveau GF doit être supérieur à l\'ancien.'], 422);
            }

            // Vérifier que le nouveau GF ne dépasse pas le plafond du poste de l'agent
            $poste = $agent->poste;
            $tubeMax = GF::find($poste->tube_max);
            if ($gfNouveau->ordre > $tubeMax->ordre) {
                return response()->json(['error' => 'Le nouveau GF dépasse le plafond autorisé pour ce poste (max ' . $tubeMax->id_gf . ').'], 422);
            }
        }

        // Règles pour le NR
        if (!empty($validated['id_nr_nouveau'])) {
            if (empty($validated['id_nr_ancien'])) {
                return response()->json(['error' => 'id_nr_ancien est requis quand id_nr_nouveau est fourni.'], 422);
            }

            $nrAncien = NR::find($validated['id_nr_ancien']);
            $nrNouveau = NR::find($validated['id_nr_nouveau']);

            if ($nrNouveau->ordre <= $nrAncien->ordre) {
                return response()->json(['error' => 'Le nouveau NR doit être supérieur à l\'ancien.'], 422);
            }
        }

        // Création de l'avancement
        $avancement = Avancement::create($validated);

        // Mise à jour des champs actuels de l'agent
        $updateData = [];
        if (!empty($validated['id_gf_nouveau'])) {
            $updateData['id_gf_actuel'] = $validated['id_gf_nouveau'];
        }
        if (!empty($validated['id_nr_nouveau'])) {
            $updateData['id_nr_actuel'] = $validated['id_nr_nouveau'];
        }
        if (!empty($updateData)) {
            $agent->update($updateData);
        }

        return response()->json($avancement, 201);
    }

    /**
     * Mettre à jour un avancement (rare, mais possible pour corriger une date par exemple)
     */
    public function update(Request $request, $id)
    {
        $avancement = Avancement::findOrFail($id);

        $validated = $request->validate([
            'date' => 'sometimes|date',
            'id_gf_ancien' => 'nullable|exists:gfs,id_gf',
            'id_gf_nouveau' => 'nullable|exists:gfs,id_gf',
            'id_nr_ancien' => 'nullable|exists:nrs,id_nr',
            'id_nr_nouveau' => 'nullable|exists:nrs,id_nr',
        ]);

        // Si on modifie les grades, il faut re-vérifier les règles (similaire au store)
        // Pour simplifier, on interdit la mise à jour des grades, on ne permet que la date.
        if (isset($validated['id_gf_nouveau']) || isset($validated['id_nr_nouveau'])) {
            return response()->json(['error' => 'La modification des grades n\'est pas autorisée. Utilisez la suppression et recréation.'], 422);
        }

        $avancement->update($validated);

        return response()->json($avancement);
    }

    /**
     * Supprimer un avancement
     * Attention : ne restaure pas automatiquement l'agent à son état antérieur.
     * Il faudrait recalculer le dernier avancement.
     */
    public function destroy($id)
    {
        $avancement = Avancement::findOrFail($id);
        $avancement->delete();

        // Optionnel : recalculer le dernier GF/NR de l'agent
        // Pour l'instant, on ne le fait pas automatiquement (évite la complexité).
        // On pourrait ajouter un événement ou une commande pour recalculer.

        return response()->json(null, 204);
    }
}
