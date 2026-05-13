<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget\BudgetPrevision;
use App\Models\Budget\Compte;
use App\Models\Budget\Realisation;
use App\Models\Budget\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function referentiels(): JsonResponse
    {
        return response()->json([
            'services' => Service::query()->orderBy('code')->get(),
            'comptes' => Compte::query()->orderBy('numero')->get(),
        ]);
    }

    /**
     * Liste des prévisions et réalisations avec écarts (réalisation − prévision annuelle ou prorata mensuel).
     */
    public function index(Request $request): JsonResponse
    {
        $annee = $request->query('annee');
        $anneeInt = $annee !== null && $annee !== '' ? (int) $annee : null;

        $previsionsQuery = BudgetPrevision::query()->with(['service', 'compte'])->orderBy('annee')->orderBy('service_id');
        $realisationsQuery = Realisation::query()->with(['service', 'compte'])->orderBy('annee')->orderBy('mois');

        if ($anneeInt !== null) {
            $previsionsQuery->where('annee', $anneeInt);
            $realisationsQuery->where('annee', $anneeInt);
        }

        $previsions = $previsionsQuery->get();
        $realisations = $realisationsQuery->get()->map(function (Realisation $r) {
            $data = $r->toArray();
            $data['ecart_vers_prevision_annuelle'] = $r->ecartVersPrevisionAnnuelle();
            $data['ecart_vers_prevision_mensuelle_proratis'] = $r->ecartVersPrevisionMensuelleProratis();

            return $data;
        });

        return response()->json([
            'annee' => $anneeInt,
            'previsions' => $previsions,
            'realisations' => $realisations,
        ]);
    }

    /**
     * Ajoute une prévision ou une réalisation (champ `type` : prevision | realisation).
     */
    public function store(Request $request): JsonResponse
    {
        $type = $request->input('type');

        if ($type === 'prevision') {
            $validated = $request->validate([
                'type' => ['required', Rule::in(['prevision'])],
                'service_id' => ['required', 'integer', 'exists:services,id'],
                'compte_id' => ['required', 'integer', 'exists:comptes,id'],
                'montant_prevu' => ['required', 'numeric'],
                'annee' => ['required', 'integer', 'min:2000', 'max:2100'],
            ]);
            unset($validated['type']);
            $row = BudgetPrevision::create($validated);

            return response()->json($row->load(['service', 'compte']), 201);
        }

        if ($type === 'realisation') {
            $validated = $request->validate([
                'type' => ['required', Rule::in(['realisation'])],
                'service_id' => ['required', 'integer', 'exists:services,id'],
                'compte_id' => ['required', 'integer', 'exists:comptes,id'],
                'montant_realise' => ['required', 'numeric'],
                'mois' => ['required', 'integer', 'min:1', 'max:12'],
                'annee' => ['required', 'integer', 'min:2000', 'max:2100'],
                'observation' => ['nullable', 'string'],
            ]);
            unset($validated['type']);
            $row = Realisation::create($validated);
            $row->load(['service', 'compte']);

            $payload = $row->toArray();
            $payload['ecart_vers_prevision_annuelle'] = $row->ecartVersPrevisionAnnuelle();
            $payload['ecart_vers_prevision_mensuelle_proratis'] = $row->ecartVersPrevisionMensuelleProratis();

            return response()->json($payload, 201);
        }

        return response()->json(['message' => 'type invalide : utiliser prevision ou realisation.'], 422);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        if ($type === 'prevision') {
            $row = BudgetPrevision::query()->findOrFail($id);
            $validated = $request->validate([
                'service_id' => ['sometimes', 'integer', 'exists:services,id'],
                'compte_id' => ['sometimes', 'integer', 'exists:comptes,id'],
                'montant_prevu' => ['sometimes', 'numeric'],
                'annee' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            ]);
            $row->update($validated);

            return response()->json($row->fresh(['service', 'compte']));
        }

        if ($type === 'realisation') {
            $row = Realisation::query()->findOrFail($id);
            $validated = $request->validate([
                'service_id' => ['sometimes', 'integer', 'exists:services,id'],
                'compte_id' => ['sometimes', 'integer', 'exists:comptes,id'],
                'montant_realise' => ['sometimes', 'numeric'],
                'mois' => ['sometimes', 'integer', 'min:1', 'max:12'],
                'annee' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
                'observation' => ['nullable', 'string'],
            ]);
            $row->update($validated);
            $row->refresh();
            $row->load(['service', 'compte']);
            $payload = $row->toArray();
            $payload['ecart_vers_prevision_annuelle'] = $row->ecartVersPrevisionAnnuelle();
            $payload['ecart_vers_prevision_mensuelle_proratis'] = $row->ecartVersPrevisionMensuelleProratis();

            return response()->json($payload);
        }

        return response()->json(['message' => 'type invalide : prevision ou realisation.'], 422);
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        if ($type === 'prevision') {
            $row = BudgetPrevision::query()->findOrFail($id);
            $row->delete();

            return response()->json(['deleted' => true, 'type' => 'prevision', 'id' => $id]);
        }

        if ($type === 'realisation') {
            $row = Realisation::query()->findOrFail($id);
            $row->delete();

            return response()->json(['deleted' => true, 'type' => 'realisation', 'id' => $id]);
        }

        return response()->json(['message' => 'type invalide.'], 422);
    }
}
