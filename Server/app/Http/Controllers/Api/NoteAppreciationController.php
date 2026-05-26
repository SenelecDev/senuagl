<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\NoteAppreciation;
use Illuminate\Http\Request;

class NoteAppreciationController extends Controller
{
    /**
     * Liste des notes d'appréciation
     */
    public function index(Request $request)
    {
        $query = NoteAppreciation::with('agent');

        if ($request->has('annee')) {
            $query->where('annee', $request->annee);
        }

        if ($request->has('matricule_agent')) {
            $query->where('matricule_agent', $request->matricule_agent);
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $notes = $query->orderBy('annee', 'desc')->paginate($perPage);

        return response()->json($notes);
    }

    /**
     * Détail d'une note
     */
    public function show($id)
    {
        $note = NoteAppreciation::with('agent')->findOrFail($id);
        return response()->json($note);
    }

    /**
     * Créer une note d'appréciation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricule_agent' => 'required|exists:agents,matricule',
            'annee' => 'required|integer|min:2000|max:2100',
            'note' => 'required|integer|min:0|max:100',
            'commentaire' => 'nullable|string|max:1000'
        ]);

        $existing = NoteAppreciation::where('matricule_agent', $validated['matricule_agent'])
            ->where('annee', $validated['annee'])
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Une note pour cet agent et cette année existe déjà.'], 422);
        }

        $note = NoteAppreciation::create($validated);

        return response()->json($note, 201);
    }

    /**
     * Mettre à jour une note
     */
    public function update(Request $request, $id)
    {
        $note = NoteAppreciation::findOrFail($id);

        $validated = $request->validate([
            'note' => 'sometimes|integer|min:0|max:100',
            'commentaire' => 'nullable|string|max:1000'
        ]);

        $note->update($validated);

        return response()->json($note);
    }

    /**
     * Supprimer une note
     */
    public function destroy($id)
    {
        $note = NoteAppreciation::findOrFail($id);
        $note->delete();

        return response()->json(null, 204);
    }

    /**
     * Notes d'un agent donné
     */
    public function getByAgent($matricule)
    {
        $agent = Agent::findOrFail($matricule);

        $notes = NoteAppreciation::where('matricule_agent', $matricule)
            ->orderBy('annee', 'desc')
            ->get();

        return response()->json([
            'agent' => $agent,
            'notes' => $notes
        ]);
    }

    /**
     * Notes par année
     */
    public function getByAnnee($annee)
    {
        $notes = NoteAppreciation::where('annee', $annee)
            ->with('agent')
            ->orderBy('note', 'desc')
            ->get();

        return response()->json([
            'annee' => $annee,
            'notes' => $notes
        ]);
    }
}
