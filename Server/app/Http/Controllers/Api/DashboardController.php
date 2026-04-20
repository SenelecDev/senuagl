<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandeConge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();

        // Statistiques générales
        $stats = [
            'conges_restants' => $user->conges_annuels_restants,
            'conges_pris' => $user->conges_annuels_total - $user->conges_annuels_restants,
            'demandes_en_attente' => DemandeConge::where('user_id', $user->id)
                                                ->where('statut', 'en_attente')
                                                ->count(),
            'demandes_approuvees' => DemandeConge::where('user_id', $user->id)
                                                ->where('statut', 'approuve')
                                                ->count(),
            'demandes_rejetees' => DemandeConge::where('user_id', $user->id)
                                               ->where('statut', 'rejete')
                                               ->count(),
        ];

        // Statistiques mensuelles
        $demandesParMois = DemandeConge::where('user_id', $user->id)
                                     ->whereYear('created_at', Carbon::now()->year)
                                     ->select(
                                         DB::raw('EXTRACT(MONTH FROM created_at) as mois'),
                                         DB::raw('COUNT(*) as total')
                                     )
                                     ->groupBy('mois')
                                     ->orderBy('mois')
                                     ->get()
                                     ->keyBy('mois');

        $statsMenuelles = [];
        for ($i = 1; $i <= 12; $i++) {
            $statsMenuelles[] = [
                'mois' => Carbon::create()->month($i)->format('M'),
                'total' => $demandesParMois->get($i)->total ?? 0,
            ];
        }

        // Prochains congés
        $prochainsConges = DemandeConge::where('user_id', $user->id)
                                     ->where('statut', 'approuve')
                                     ->where('date_debut', '>=', Carbon::now())
                                     ->orderBy('date_debut', 'asc')
                                     ->limit(5)
                                     ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'stats_mensuelles' => $statsMenuelles,
                'prochains_conges' => $prochainsConges,
            ],
        ]);
    }

    public function recentActivity(Request $request)
    {
        $user = $request->user();

        $activites = DemandeConge::where('user_id', $user->id)
                                ->with(['validateur'])
                                ->orderBy('updated_at', 'desc')
                                ->limit(10)
                                ->get()
                                ->map(function ($demande) {
                                    return [
                                        'id' => $demande->id,
                                        'type' => $demande->type_label,
                                        'statut' => $demande->statut_label,
                                        'date_debut' => $demande->date_debut,
                                        'date_fin' => $demande->date_fin,
                                        'duree_jours' => $demande->duree_jours,
                                        'date_creation' => $demande->created_at,
                                        'date_validation' => $demande->date_validation,
                                        'valide_par' => $demande->validateur ? $demande->validateur->full_name : null,
                                    ];
                                });

        return response()->json([
            'success' => true,
            'data' => $activites,
        ]);
    }

    public function statsManager(Request $request)
    {
        $user = $request->user();

        if (!$user->canValidateLeave()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $query = DemandeConge::query();

        // Les managers ne voient que les demandes de leurs subordonnés
        if ($user->role->nom === 'superieur') {
            $subordinatesIds = $user->subordinates->pluck('id');
            $query->whereIn('user_id', $subordinatesIds);
        }

        $stats = [
            'demandes_en_attente' => $query->clone()->where('statut', 'en_attente')->count(),
            'demandes_approuvees' => $query->clone()->where('statut', 'approuve')->count(),
            'demandes_rejetees' => $query->clone()->where('statut', 'rejete')->count(),
            'demandes_ce_mois' => $query->clone()->whereMonth('created_at', Carbon::now()->month)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
