<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poste;
use App\Models\Unite;
use App\Models\GF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosteController extends Controller
{
    private function resolveUniteScopeIds(string $uniteId): array
    {
        // Inclure l'unité demandée + tous ses descendants (départements -> services, etc.)
        $scope = [$uniteId];
        $frontier = [$uniteId];

        while (! empty($frontier)) {
            $children = Unite::query()
                ->whereIn('id_parent', $frontier)
                ->pluck('id_unite')
                ->all();

            $children = array_values(array_diff($children, $scope));
            if (empty($children)) {
                break;
            }

            $scope = array_merge($scope, $children);
            $frontier = $children;
        }

        return $scope;
    }

    // Liste des postes (avec filtres)
    public function index(Request $request)
    {
        $query = Poste::with(['unite', 'tubeMin', 'tubeMax']);

        if ($request->has('unite')) {
            $uniteId = (string) $request->unite;
            $scopeIds = $this->resolveUniteScopeIds($uniteId);
            $query->whereIn('id_unite', $scopeIds);
        }

        if ($request->has('vacants')) {
            $query->has('agents', '<', DB::raw('postes.effectif_theorique'));
        }

        $postes = $query->get();

        foreach ($postes as $poste) {
            $poste->effectif_reel = $poste->effectif_reel;
            $poste->postes_vacants = $poste->postes_vacants;
            $poste->taux_occupation = $poste->taux_occupation;
        }

        return response()->json($postes);
    }

    // Détail d'un poste (avec agents occupants)
    public function show($id)
    {
        $poste = Poste::with([
            'unite',
            'tubeMin',
            'tubeMax',
            'agents' => function ($q) {
                $q->with(['gfActuel', 'nrActuel']);
            }
        ])->findOrFail($id);

        $poste->effectif_reel = $poste->effectif_reel;
        $poste->postes_vacants = $poste->postes_vacants;
        $poste->taux_occupation = $poste->taux_occupation;

        return response()->json($poste);
    }

    // Créer un poste
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_post' => 'required|string|max:20|unique:postes',
            'intitule' => 'required|string|max:100',
            'effectif_theorique' => 'sometimes|integer|min:1',
            'tube_min' => 'required|exists:gfs,id_gf',
            'tube_max' => 'required|exists:gfs,id_gf',
            'id_unite' => 'required|exists:unites,id_unite',
        ]);

        $minOrdre = GF::find($validated['tube_min'])->ordre;
        $maxOrdre = GF::find($validated['tube_max'])->ordre;
        if ($minOrdre > $maxOrdre) {
            return response()->json(['error' => 'tube_min doit être inférieur ou égal à tube_max'], 422);
        }

        $poste = Poste::create($validated);
        return response()->json($poste, 201);
    }

    // Mettre à jour un poste
    public function update(Request $request, $id)
    {
        $poste = Poste::findOrFail($id);

        $validated = $request->validate([
            'intitule' => 'sometimes|string|max:100',
            'effectif_theorique' => 'sometimes|integer|min:1',
            'tube_min' => 'sometimes|exists:gfs,id_gf',
            'tube_max' => 'sometimes|exists:gfs,id_gf',
            'id_unite' => 'sometimes|exists:unites,id_unite',
        ]);

        if (isset($validated['tube_min']) && isset($validated['tube_max'])) {
            $min = GF::find($validated['tube_min'])->ordre;
            $max = GF::find($validated['tube_max'])->ordre;
            if ($min > $max) return response()->json(['error' => 'tube_min doit être ≤ tube_max'], 422);
        } elseif (isset($validated['tube_min'])) {
            $oldMax = GF::find($poste->tube_max)->ordre;
            $newMin = GF::find($validated['tube_min'])->ordre;
            if ($newMin > $oldMax) return response()->json(['error' => 'tube_min ne peut pas dépasser tube_max actuel'], 422);
        } elseif (isset($validated['tube_max'])) {
            $oldMin = GF::find($poste->tube_min)->ordre;
            $newMax = GF::find($validated['tube_max'])->ordre;
            if ($oldMin > $newMax) return response()->json(['error' => 'tube_max ne peut pas être inférieur à tube_min actuel'], 422);
        }

        $poste->update($validated);
        return response()->json($poste);
    }

    // Supprimer un poste (uniquement s'il n'est pas occupé)
    public function destroy($id)
    {
        $poste = Poste::findOrFail($id);
        if ($poste->effectif_reel > 0) {
            return response()->json(['error' => 'Impossible de supprimer un poste occupé'], 422);
        }
        $poste->delete();
        return response()->json(null, 204);
    }

    // Postes vacants (global)
    public function postesVacants()
    {
        $postes = Poste::with('unite')->get();
        $vacants = $postes->filter(fn($p) => $p->postes_vacants > 0)
            ->map(fn($p) => [
                'id_post' => $p->id_post,
                'intitule' => $p->intitule,
                'unite' => $p->unite->nom,
                'effectif_theorique' => $p->effectif_theorique,
                'effectif_reel' => $p->effectif_reel,
                'postes_vacants' => $p->postes_vacants,
            ]);

        return response()->json([
            'total_vacants' => $postes->sum('postes_vacants'),
            'postes' => $vacants->values()
        ]);
    }

    // Arbre des unités avec leurs postes
    public function getArbrePostes()
    {
        $unites = Unite::with(['postes.tubeMin', 'postes.tubeMax'])->get();
        return response()->json($unites);
    }
}