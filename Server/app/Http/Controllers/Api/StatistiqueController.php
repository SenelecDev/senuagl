<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Poste;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatistiqueController extends Controller
{
    // Pyramide des âges
    public function pyramideAges()
    {
        $agents = Agent::select(
                'sexe',
                DB::raw('EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_naissance)) as age')
            )->get();
        
        $tranches = [
            'Moins de 25' => [0, 24],
            '25-34' => [25, 34],
            '35-44' => [35, 44],
            '45-54' => [45, 54],
            '55 et plus' => [55, 200]
        ];
        
        $resultat = [];
        foreach ($tranches as $label => [$min, $max]) {
            $resultat[$label] = [
                'hommes' => $agents->where('sexe', 'M')->filter(fn($a) => $a->age >= $min && $a->age <= $max)->count(),
                'femmes' => $agents->where('sexe', 'F')->filter(fn($a) => $a->age >= $min && $a->age <= $max)->count(),
                'total' => $agents->filter(fn($a) => $a->age >= $min && $a->age <= $max)->count(),
            ];
        }
        
        return response()->json($resultat);
    }
    
    // Répartition H/F (globale)
    public function repartitionHF()
    {
        $total = Agent::count();
        
        $hommes = Agent::where('sexe', 'M')->count();
        $femmes = Agent::where('sexe', 'F')->count();
        
        return response()->json([
            'hommes' => [
                'nombre' => $hommes,
                'pourcentage' => $total > 0 ? round(($hommes / $total) * 100, 1) : 0
            ],
            'femmes' => [
                'nombre' => $femmes,
                'pourcentage' => $total > 0 ? round(($femmes / $total) * 100, 1) : 0
            ],
            'total' => $total
        ]);
    }
    
    // Répartition H/F par service
    public function repartitionHFParService()
    {
        $stats = Agent::select(
                'unites.id_unite',
                'unites.nom as service',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN sexe = \'M\' THEN 1 ELSE 0 END) as hommes'),
                DB::raw('SUM(CASE WHEN sexe = \'F\' THEN 1 ELSE 0 END) as femmes')
            )
            ->join('postes', 'agents.id_post', '=', 'postes.id_post')
            ->join('unites', 'postes.id_unite', '=', 'unites.id_unite')
            ->groupBy('unites.id_unite', 'unites.nom')
            ->get();
        
        foreach ($stats as $stat) {
            $stat->pourcentage_hommes = $stat->total > 0 ? round(($stat->hommes / $stat->total) * 100, 1) : 0;
            $stat->pourcentage_femmes = $stat->total > 0 ? round(($stat->femmes / $stat->total) * 100, 1) : 0;
        }
        
        return response()->json($stats);
    }
    
    // Départs retraite prévisionnels (âge limite 60 ans)
    public function departsRetraite()
    {
        $agents = Agent::select(
                'matricule',
                'nom',
                'prenom',
                'date_naissance',
                'id_post'
            )
            ->with('poste:id_post,intitule')
            ->get();
        
        $aujourdhui = now()->startOfDay();
        
        $resultat = [
            'moins_1_an' => [],
            'entre_1_et_2_ans' => [],
            'entre_2_et_3_ans' => [],
            'entre_1_et_3_ans' => [],
            'entre_3_et_5_ans' => [],
            'plus_5_ans' => []
        ];
        
        foreach ($agents as $agent) {
            $anneeRetraite = Carbon::parse($agent->date_naissance)->addYears(60)->year;
            $dateRetraite = Carbon::create($anneeRetraite, 12, 31)->startOfDay();
            $moisAvantRetraite = $aujourdhui->diffInMonths($dateRetraite, false);

            $agent->age = $agent->age;
            $agent->date_retraite = $dateRetraite->toDateString();
            $agent->mois_avant_retraite = max(0, (int) ceil($moisAvantRetraite));
            
            if ($moisAvantRetraite <= 12) {
                $resultat['moins_1_an'][] = $agent;
            } elseif ($moisAvantRetraite <= 24) {
                $resultat['entre_1_et_2_ans'][] = $agent;
                $resultat['entre_1_et_3_ans'][] = $agent;
            } elseif ($moisAvantRetraite <= 36) {
                $resultat['entre_2_et_3_ans'][] = $agent;
                $resultat['entre_1_et_3_ans'][] = $agent;
            } elseif ($moisAvantRetraite <= 60) {
                $resultat['entre_3_et_5_ans'][] = $agent;
            } else {
                $resultat['plus_5_ans'][] = $agent;
            }
        }
        
        // Compter par catégorie
        $comptage = [
            'moins_1_an' => count($resultat['moins_1_an']),
            'entre_1_et_2_ans' => count($resultat['entre_1_et_2_ans']),
            'entre_2_et_3_ans' => count($resultat['entre_2_et_3_ans']),
            'entre_1_et_3_ans' => count($resultat['entre_1_et_3_ans']),
            'entre_3_et_5_ans' => count($resultat['entre_3_et_5_ans']),
            'plus_5_ans' => count($resultat['plus_5_ans']),
            'total' => $agents->count()
        ];
        
        return response()->json([
            'comptage' => $comptage,
            'liste' => $resultat
        ]);
    }
    
    // Agents hors plafond (anomalies)
    public function plafonnementAnomalies()
    {
        $agents = Agent::with(['poste.tubeMin', 'poste.tubeMax', 'gfActuel'])
            ->whereRaw('EXISTS (
                SELECT 1 FROM postes p 
                JOIN gfs gf_max ON p.tube_max = gf_max.id_gf
                JOIN gfs gf_agent ON agents.id_gf_actuel = gf_agent.id_gf
                WHERE p.id_post = agents.id_post 
                AND gf_agent.ordre > gf_max.ordre
            )')
            ->orWhereRaw('EXISTS (
                SELECT 1 FROM postes p 
                JOIN gfs gf_min ON p.tube_min = gf_min.id_gf
                JOIN gfs gf_agent ON agents.id_gf_actuel = gf_agent.id_gf
                WHERE p.id_post = agents.id_post 
                AND gf_agent.ordre < gf_min.ordre
            )')
            ->get();
        
        foreach ($agents as $agent) {
            $agent->tube_min = $agent->poste->tubeMin->id_gf;
            $agent->tube_max = $agent->poste->tubeMax->id_gf;
            $agent->type_anomalie = $agent->gfActuel->ordre > $agent->poste->tubeMax->ordre 
                ? 'DEPASSE_PLAFOND' 
                : 'SOUS_PLANCHER';
        }
        
        return response()->json($agents);
    }
    
    // Agents bloqués (au plafond depuis + de 3 ans sans promotion)
    public function agentsBloques()
    {
        $agents = Agent::with(['poste.tubeMax', 'gfActuel'])
            ->whereRaw('EXISTS (
                SELECT 1 FROM postes p 
                JOIN gfs gf_max ON p.tube_max = gf_max.id_gf
                JOIN gfs gf_agent ON agents.id_gf_actuel = gf_agent.id_gf
                WHERE p.id_post = agents.id_post 
                AND gf_agent.ordre >= gf_max.ordre
            )')
            ->get();
        
        $resultat = [];
        foreach ($agents as $agent) {
            $dernierePromo = $agent->derniere_promotion_gf;
            if ($dernierePromo) {
                $anneesSansPromo = now()->diffInYears($dernierePromo->date);
                if ($anneesSansPromo >= 3) {
                    $agent->annees_sans_promotion = $anneesSansPromo;
                    $resultat[] = $agent;
                }
            } else {
                // Jamais eu de promotion
                $anneesDepuisEmbauche = now()->diffInYears($agent->date_embauche);
                if ($anneesDepuisEmbauche >= 3) {
                    $agent->annees_sans_promotion = $anneesDepuisEmbauche;
                    $resultat[] = $agent;
                }
            }
        }
        
        return response()->json($resultat);
    }
}
