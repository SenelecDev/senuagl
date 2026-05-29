<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget\ProjetInvestissement;
use Illuminate\Http\Request;

class ProjetInvestissementController extends Controller
{
    public function index(Request $request)
    {
        $annee = $request->query('annee', date('Y'));
        $projets = ProjetInvestissement::where('annee', $annee)->get();

        return response()->json([
            'projets' => $projets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_projet' => 'required|string',
            'libelle' => 'required|string',
            'bailleur' => 'nullable|string',
            'cr' => 'nullable|string',
            'montant_marche' => 'numeric',
            'cout_projet' => 'numeric',
            'fp_annee' => 'numeric',
            'fe_annee' => 'numeric',
            'annee' => 'required|integer'
        ]);

        $projet = ProjetInvestissement::create($validated);

        return response()->json($projet, 201);
    }

    public function update(Request $request, ProjetInvestissement $projet)
    {
        $validated = $request->validate([
            'libelle' => 'string',
            'bailleur' => 'nullable|string',
            'cr' => 'nullable|string',
            'montant_marche' => 'numeric',
            'cout_projet' => 'numeric',
            'fp_annee' => 'numeric',
            'fe_annee' => 'numeric',
        ]);

        $projet->update($validated);

        return response()->json($projet);
    }

    public function destroy(ProjetInvestissement $projet)
    {
        $projet->delete();
        return response()->noContent();
    }
}
