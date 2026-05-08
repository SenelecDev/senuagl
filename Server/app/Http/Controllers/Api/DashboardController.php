<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Poste;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function kpi()
    {
        $totalAgents = Agent::count();
        
        $postes = Poste::all();
        $postesVacants = $postes->sum(function ($poste) {
            return $poste->postes_vacants;
        });
        
        $departsRetraite5ans = Agent::whereRaw(
            'EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_naissance)) >= 55'
        )->count();
        
        $departsRetraite12mois = Agent::whereRaw(
            'EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_naissance)) >= 59'
        )->count();
        
        $anomalies = Agent::whereRaw('EXISTS (
            SELECT 1 FROM postes p 
            JOIN gfs gf_max ON p.tube_max = gf_max.id_gf
            JOIN gfs gf_agent ON agents.id_gf_actuel = gf_agent.id_gf
            WHERE p.id_post = agents.id_post 
            AND gf_agent.ordre > gf_max.ordre
        )')->count();
        
        return response()->json([
            'total_agents' => $totalAgents,
            'postes_vacants' => $postesVacants,
            'taux_vacants' => $totalAgents > 0 ? round(($postesVacants / $totalAgents) * 100, 1) : 0,
            'departs_retraite_5ans' => $departsRetraite5ans,
            'departs_retraite_12mois' => $departsRetraite12mois,
            'anomalies_plafonnement' => $anomalies,
        ]);
    }
}