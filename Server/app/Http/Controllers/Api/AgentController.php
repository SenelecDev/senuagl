<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Poste;
use App\Models\GF;
use App\Models\NR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgentController extends Controller
{
    // Liste des agents (champs principaux uniquement)
    public function index(Request $request)
    {
        $query = Agent::with(['poste.unite', 'gfActuel', 'nrActuel']);
        
        // Filtres
        if ($request->has('service')) {
            $query->whereHas('poste.unite', function ($q) use ($request) {
                $q->where('id_unite', $request->service);
            });
        }
        
        if ($request->has('gf')) {
            $query->where('id_gf_actuel', $request->gf);
        }
        
        if ($request->has('sexe')) {
            $query->where('sexe', $request->sexe);
        }
        
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->search . '%')
                  ->orWhere('prenom', 'like', '%' . $request->search . '%')
                  ->orWhere('matricule', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->has('plafonne') && $request->plafonne == 'true') {
            $query->whereRaw('EXISTS (
                SELECT 1 FROM postes p 
                JOIN gfs gf_max ON p.tube_max = gf_max.id_gf
                JOIN gfs gf_agent ON agents.id_gf_actuel = gf_agent.id_gf
                WHERE p.id_post = agents.id_post 
                AND gf_agent.ordre >= gf_max.ordre
            )');
        }
        
        $agents = $query->orderBy('nom')->paginate(20);
        
        // Ajout des infos calculées pour la liste
        foreach ($agents as $agent) {
            $agent->age = $agent->age;
            $agent->anciennete = $agent->anciennete;
            $agent->est_plafonne = $agent->est_plafonne;
        }
        
        return response()->json($agents);
    }
    
    // Détail complet d'un agent (tous les champs)
    public function show($matricule)
    {
        $agent = Agent::with([
            'poste' => function ($q) {
                $q->with(['unite.parent', 'tubeMin', 'tubeMax']);
            },
            'gfActuel',
            'nrActuel',
            'avancements' => function ($q) {
                $q->with(['gfAncien', 'gfNouveau', 'nrAncien', 'nrNouveau'])
                  ->orderBy('date', 'desc');
            }
        ])->findOrFail($matricule);
        
        // Ajout des infos calculées
        $agent->age = $agent->age;
        $agent->anciennete = $agent->anciennete;
        $agent->est_plafonne = $agent->est_plafonne;
        $agent->date_derniere_promotion_gf = $agent->derniere_promotion_gf?->date;
        $agent->date_dernier_changement_nr = $agent->dernier_changement_nr?->date;
        
        return response()->json($agent);
    }
    
    // Créer un agent
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Identifiants
            'matricule' => 'required|string|max:10|unique:agents',
            'titre' => 'nullable|string|max:10',
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'sexe' => 'required|in:M,F',
            
            // Naissance
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'nationalite' => 'nullable|string|max:30',
            'num_identite' => 'nullable|string|max:50',
            'ethnie' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            
            // Famille
            'situation_familiale' => 'nullable|string|max:30',
            'nombre_enfants' => 'nullable|integer|min:0',
            'enfants_21_ans' => 'nullable|integer|min:0',
            'enfants_18_ans' => 'nullable|integer|min:0',
            
            // Cotisations
            'part_trimf' => 'nullable|boolean',
            'part_ir' => 'nullable|boolean',
            'num_ipres' => 'nullable|string|max:50',
            'num_secu_social' => 'nullable|string|max:50',
            
            // Emploi
            'date_embauche' => 'required|date',
            'organisation' => 'nullable|string|max:100',
            'centre_de_responsabilite' => 'nullable|string|max:100',
            'lieu' => 'nullable|string|max:100',
            'situation_affectation' => 'nullable|string|max:100',
            
            // Salaire
            'salaire_base' => 'nullable|numeric|min:0',
            'mode_reglement' => 'nullable|string|max:50',
            
            // Banque
            'banque' => 'nullable|string|max:50',
            'compte' => 'nullable|string|max:50',
            'domiciliation' => 'nullable|string|max:100',
            'rib' => 'nullable|string|max:50',
            
            // Syndicat
            'syndicat' => 'nullable|string|max:50',
            
            // Clés étrangères
            'id_post' => 'required|exists:postes,id_post',
            'id_gf_actuel' => 'required|exists:gfs,id_gf',
            'id_nr_actuel' => 'required|exists:nrs,id_nr',
        ]);
        
        // Vérifier que le GF est dans le tube du poste
        $poste = Poste::find($validated['id_post']);
        if (!$poste->estDansTube($validated['id_gf_actuel'])) {
            return response()->json([
                'error' => 'Le GF attribué est hors du tube autorisé pour ce poste'
            ], 422);
        }
        
        $agent = Agent::create($validated);
        
        // Créer l'avancement initial (embauche)
        DB::table('avancements')->insert([
            'date' => $validated['date_embauche'],
            'matricule_agent' => $validated['matricule'],
            'id_gf_nouveau' => $validated['id_gf_actuel'],
            'id_nr_nouveau' => $validated['id_nr_actuel'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return response()->json($agent, 201);
    }
    
    // Modifier un agent
    public function update(Request $request, $matricule)
    {
        $agent = Agent::findOrFail($matricule);
        
        $validated = $request->validate([
            'titre' => 'nullable|string|max:10',
            'nom' => 'sometimes|string|max:50',
            'prenom' => 'sometimes|string|max:50',
            'sexe' => 'sometimes|in:M,F',
            'date_naissance' => 'sometimes|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'nationalite' => 'nullable|string|max:30',
            'num_identite' => 'nullable|string|max:50',
            'ethnie' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            'situation_familiale' => 'nullable|string|max:30',
            'nombre_enfants' => 'nullable|integer|min:0',
            'enfants_21_ans' => 'nullable|integer|min:0',
            'enfants_18_ans' => 'nullable|integer|min:0',
            'part_trimf' => 'nullable|boolean',
            'part_ir' => 'nullable|boolean',
            'num_ipres' => 'nullable|string|max:50',
            'num_secu_social' => 'nullable|string|max:50',
            'date_embauche' => 'sometimes|date',
            'organisation' => 'nullable|string|max:100',
            'centre_de_responsabilite' => 'nullable|string|max:100',
            'lieu' => 'nullable|string|max:100',
            'situation_affectation' => 'nullable|string|max:100',
            'salaire_base' => 'nullable|numeric|min:0',
            'mode_reglement' => 'nullable|string|max:50',
            'banque' => 'nullable|string|max:50',
            'compte' => 'nullable|string|max:50',
            'domiciliation' => 'nullable|string|max:100',
            'rib' => 'nullable|string|max:50',
            'syndicat' => 'nullable|string|max:50',
            'id_post' => 'sometimes|exists:postes,id_post',
            'id_gf_actuel' => 'sometimes|exists:gfs,id_gf',
            'id_nr_actuel' => 'sometimes|exists:nrs,id_nr',
        ]);
        
        // Vérifier le plafonnement si GF change
        if (isset($validated['id_gf_actuel'])) {
            $posteId = $validated['id_post'] ?? $agent->id_post;
            $poste = Poste::find($posteId);
            if (!$poste->estDansTube($validated['id_gf_actuel'])) {
                return response()->json([
                    'error' => 'Le GF attribué est hors du tube autorisé pour ce poste'
                ], 422);
            }
        }
        
        $agent->update($validated);
        
        return response()->json($agent);
    }
    
    // Supprimer un agent
    public function destroy($matricule)
    {
        $agent = Agent::findOrFail($matricule);
        $agent->delete();
        
        return response()->json(null, 204);
    }
    
    // Statistiques par service
    public function statsParService()
    {
        $stats = Agent::select(
                'unites.id_unite',
                'unites.nom as service',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN sexe = \'M\' THEN 1 ELSE 0 END) as hommes'),
                DB::raw('SUM(CASE WHEN sexe = \'F\' THEN 1 ELSE 0 END) as femmes'),
                DB::raw('AVG(EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_naissance))) as age_moyen')
            )
            ->join('postes', 'agents.id_post', '=', 'postes.id_post')
            ->join('unites', 'postes.id_unite', '=', 'unites.id_unite')
            ->groupBy('unites.id_unite', 'unites.nom')
            ->get();
        
        return response()->json($stats);
    }
}