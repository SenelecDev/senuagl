<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget\BudgetPrevision;
use App\Models\Budget\Compte;
use App\Models\Budget\Realisation;
use App\Models\Budget\Engagement;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function referentiels(): JsonResponse
    {
        return response()->json([
            'comptes' => Compte::query()
                ->with('parent')
                ->withCount('enfants')
                ->orderBy('numero')
                ->get(),
        ]);
    }

    public function storeCompte(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero' => 'required|string|unique:comptes,numero',
            'intitule' => 'required|string',
            'parent_numero' => 'nullable|string|exists:comptes,numero'
        ]);

        $parentId = null;
        if (!empty($validated['parent_numero'])) {
            $parentId = Compte::where('numero', $validated['parent_numero'])->value('id');
        }

        $compte = Compte::create([
            'numero' => $validated['numero'],
            'intitule' => $validated['intitule'],
            'parent_id' => $parentId
        ]);

        // Return the compte with parent to match the referentiels payload
        $compte->load('parent');
        $compte->enfants_count = 0;

        return response()->json($compte, 201);
    }

    private function compteEstSaisissable(int $compteId): bool
    {
        return ! Compte::query()
            ->where('id', $compteId)
            ->whereHas('enfants')
            ->exists();
    }

    private function compteNonSaisissableResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Ce compte est un compte de regroupement. Saisir une ligne sur un sous-compte.',
        ], 422);
    }

    /**
     * Liste des prévisions, engagements et réalisations.
     */
    public function index(Request $request): JsonResponse
    {
        $annee = $request->query('annee');
        $anneeInt = $annee !== null && $annee !== '' ? (int) $annee : null;

        $previsionsQuery = BudgetPrevision::query()
            ->with(['compte.parent'])
            ->orderBy('annee');
        $realisationsQuery = Realisation::query()
            ->with(['compte.parent'])
            ->orderBy('date_realisation');
        $engagementsQuery = Engagement::query()
            ->with(['compte.parent'])
            ->orderBy('date_engagement');

        if ($anneeInt !== null) {
            $previsionsQuery->where('annee', $anneeInt);
            $realisationsQuery->whereYear('date_realisation', $anneeInt);
            $engagementsQuery->whereYear('date_engagement', $anneeInt);
        }

        return response()->json([
            'annee' => $anneeInt,
            'previsions' => $previsionsQuery->get(),
            'realisations' => $realisationsQuery->get(),
            'engagements' => $engagementsQuery->get(),
        ]);
    }

    /**
     * Ajoute une prévision, une réalisation ou un engagement.
     */
    public function store(Request $request): JsonResponse
    {
        $type = $request->input('type');

        if ($type === 'prevision') {
            $validated = $request->validate([
                'type' => ['required', Rule::in(['prevision'])],
                'compte_id' => ['required', 'integer', 'exists:comptes,id'],
                'montant_prevu' => ['required', 'numeric'],
                'annee' => ['required', 'integer', 'min:2000', 'max:2100'],
                'mois' => ['required', 'integer', 'min:1', 'max:12'],
            ]);
            unset($validated['type']);
            $row = BudgetPrevision::query()->updateOrCreate(
                [
                    'compte_id' => $validated['compte_id'],
                    'annee' => $validated['annee'],
                    'mois' => $validated['mois'],
                ],
                ['montant_prevu' => $validated['montant_prevu']],
            );

            return response()->json(
                $row->load(['compte']),
                $row->wasRecentlyCreated ? 201 : 200,
            );
        }

        if ($type === 'realisation') {
            $validated = $request->validate([
                'type' => ['required', Rule::in(['realisation'])],
                'compte_id' => ['required', 'integer', 'exists:comptes,id'],
                'montant_realise' => ['required', 'numeric'],
                'date_realisation' => ['required', 'date'],
                'observation' => ['nullable', 'string'],
            ]);
            unset($validated['type']);
            $row = Realisation::create($validated);
            $row->load(['compte']);
            return response()->json($row, 201);
        }

        if ($type === 'engagement') {
            $validated = $request->validate([
                'type' => ['required', Rule::in(['engagement'])],
                'compte_id' => ['required', 'integer', 'exists:comptes,id'],
                'montant_engage' => ['required', 'numeric'],
                'date_engagement' => ['required', 'date'],
                'observation' => ['nullable', 'string'],
            ]);
            unset($validated['type']);
            $row = Engagement::create($validated);
            $row->load(['compte']);
            return response()->json($row, 201);
        }

        return response()->json(['message' => 'type invalide : utiliser prevision, realisation ou engagement.'], 422);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        if ($type === 'prevision') {
            $row = BudgetPrevision::query()->findOrFail($id);
            $validated = $request->validate([
                'compte_id' => ['sometimes', 'integer', 'exists:comptes,id'],
                'montant_prevu' => ['sometimes', 'numeric'],
                'annee' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
                'mois' => ['sometimes', 'integer', 'min:1', 'max:12'],
            ]);
            $row->update($validated);
            return response()->json($row->fresh(['compte']));
        }

        if ($type === 'realisation') {
            $row = Realisation::query()->findOrFail($id);
            $validated = $request->validate([
                'compte_id' => ['sometimes', 'integer', 'exists:comptes,id'],
                'montant_realise' => ['sometimes', 'numeric'],
                'date_realisation' => ['sometimes', 'date'],
                'observation' => ['nullable', 'string'],
            ]);
            $row->update($validated);
            return response()->json($row->fresh(['compte']));
        }

        if ($type === 'engagement') {
            $row = Engagement::query()->findOrFail($id);
            $validated = $request->validate([
                'compte_id' => ['sometimes', 'integer', 'exists:comptes,id'],
                'montant_engage' => ['sometimes', 'numeric'],
                'date_engagement' => ['sometimes', 'date'],
                'observation' => ['nullable', 'string'],
            ]);
            $row->update($validated);
            return response()->json($row->fresh(['compte']));
        }

        return response()->json(['message' => 'type invalide : prevision, realisation ou engagement.'], 422);
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        if ($type === 'prevision') {
            BudgetPrevision::query()->findOrFail($id)->delete();
            return response()->json(['deleted' => true, 'type' => 'prevision', 'id' => $id]);
        }

        if ($type === 'realisation') {
            Realisation::query()->findOrFail($id)->delete();
            return response()->json(['deleted' => true, 'type' => 'realisation', 'id' => $id]);
        }

        if ($type === 'engagement') {
            Engagement::query()->findOrFail($id)->delete();
            return response()->json(['deleted' => true, 'type' => 'engagement', 'id' => $id]);
        }

        return response()->json(['message' => 'type invalide.'], 422);
    }

    /**
     * Estimation budgétaire par extrapolation trimestrielle.
     *
     * Pour chaque section-compte, cumule engagements + réalisations par trimestre
     * et extrapole sur 12 mois.
     */
    public function estimation(Request $request): JsonResponse
    {
        $annee = (int) $request->query('annee', date('Y'));

        // Récupérer les comptes-section (ceux dont le numéro commence par SECTION-)
        $sections = Compte::query()
            ->where('numero', 'LIKE', 'SECTION-%')
            ->get();

        $sectionData = [];

        foreach ($sections as $section) {
            $descendantIds = $this->collectDescendantIds($section->id);

            $quarterEnds = [
                1 => "{$annee}-03-31",
                2 => "{$annee}-06-30",
                3 => "{$annee}-09-30",
                4 => "{$annee}-12-31",
            ];

            $coefficients = [
                1 => 12 / 3,   // ×4
                2 => 12 / 6,   // ×2
                3 => 12 / 9,   // ×4/3
                4 => 12 / 12,  // ×1
            ];

            $result = [
                'compte_id' => $section->id,
                'numero'    => $section->numero,
                'intitule'  => $section->intitule,
            ];

            foreach ($quarterEnds as $q => $endDate) {
                $startDate = "{$annee}-01-01";

                $cumulEngage = Engagement::query()
                    ->whereIn('compte_id', $descendantIds)
                    ->whereBetween('date_engagement', [$startDate, $endDate])
                    ->sum('montant_engage');

                $cumulRealise = Realisation::query()
                    ->whereIn('compte_id', $descendantIds)
                    ->whereBetween('date_realisation', [$startDate, $endDate])
                    ->sum('montant_realise');

                $cumul = round((float) $cumulEngage + (float) $cumulRealise, 2);
                $estimation = round($cumul * $coefficients[$q], 2);

                $result["cumul_q{$q}"] = $cumul;
                $result["estimation_q{$q}"] = $estimation;
            }

            // Budget prévu pour cette section (somme des prévisions des comptes enfants)
            $totalPrevu = BudgetPrevision::query()
                ->whereIn('compte_id', $descendantIds)
                ->where('annee', $annee)
                ->sum('montant_prevu');

            $result['budget_prevu'] = round((float) $totalPrevu, 2);

            $sectionData[] = $result;
        }

        return response()->json([
            'annee'    => $annee,
            'sections' => $sectionData,
        ]);
    }

    /**
     * Collecte récursivement tous les IDs descendants d'un compte.
     */
    private function collectDescendantIds(int $compteId): array
    {
        $ids = [$compteId];
        $children = Compte::query()->where('parent_id', $compteId)->pluck('id')->all();

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->collectDescendantIds($childId));
        }

        return $ids;
    }
}
