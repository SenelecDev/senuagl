<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget\Investissement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestissementController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = Investissement::query()->orderByDesc('id')->get();

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'montant_initial' => ['required', 'numeric', 'min:0'],
            'taux_actualisation' => ['required', 'numeric', 'gt:-1'],
            'van' => ['nullable', 'numeric'],
            'tri' => ['nullable', 'numeric'],
            'drci' => ['nullable', 'numeric'],
        ]);

        $row = Investissement::create($validated);

        return response()->json($row, 201);
    }

    /**
     * Calcule flux nets, flux actualisés, VAN, TRI, DRCI à partir des séries recettes / charges.
     * Optionnel : `investissement_id` pour persister van, tri, drci sur l'enregistrement.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'montant_initial' => ['required', 'numeric', 'min:0'],
            'taux_actualisation' => ['required', 'numeric', 'gt:-1'],
            'recettes' => ['required', 'array', 'min:1'],
            'recettes.*' => ['numeric'],
            'charges' => ['required', 'array', 'min:1'],
            'charges.*' => ['numeric'],
            'investissement_id' => ['nullable', 'integer', 'exists:investissements,id'],
        ]);

        if (count($validated['recettes']) !== count($validated['charges'])) {
            return response()->json([
                'message' => 'Les tableaux recettes et charges doivent avoir la même longueur.',
            ], 422);
        }

        $resultats = Investissement::calculerIndicateurs(
            (float) $validated['montant_initial'],
            (float) $validated['taux_actualisation'],
            $validated['recettes'],
            $validated['charges'],
        );

        if (! empty($validated['investissement_id'])) {
            $inv = Investissement::query()->findOrFail($validated['investissement_id']);
            $inv->update([
                'van' => $resultats['van'],
                'tri' => $resultats['tri'],
                'drci' => $resultats['drci'],
            ]);
            $resultats['investissement_id'] = $inv->id;
            $resultats['persiste'] = true;
        }

        return response()->json($resultats);
    }
}
