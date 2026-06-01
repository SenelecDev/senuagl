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

        $statuts = DemandeConge::where('user_id', $user->id)
            ->selectRaw("
                SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as demandes_en_attente,
                SUM(CASE WHEN statut = 'approuve' THEN 1 ELSE 0 END) as demandes_approuvees,
                SUM(CASE WHEN statut = 'rejete' THEN 1 ELSE 0 END) as demandes_rejetees
            ")
            ->first();

        $stats = [
            'conges_restants' => $user->conges_annuels_restants,
            'conges_pris' => $user->conges_annuels_total - $user->conges_annuels_restants,
            'demandes_en_attente' => (int) ($statuts->demandes_en_attente ?? 0),
            'demandes_approuvees' => (int) ($statuts->demandes_approuvees ?? 0),
            'demandes_rejetees' => (int) ($statuts->demandes_rejetees ?? 0),
        ];

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

        $soldesParType = DemandeConge::where('user_id', $user->id)
            ->whereIn('statut', ['en_attente', 'approuve'])
            ->selectRaw("
                type_demande,
                SUM(CASE WHEN statut = 'approuve' THEN duree_jours ELSE 0 END) as jours_approuves,
                SUM(CASE WHEN statut = 'en_attente' THEN duree_jours ELSE 0 END) as jours_en_attente
            ")
            ->groupBy('type_demande')
            ->get()
            ->keyBy('type_demande');

        $prochainsConges = DemandeConge::where('user_id', $user->id)
            ->where('statut', 'approuve')
            ->where('date_debut', '>=', Carbon::now())
            ->select('id', 'type_demande', 'statut', 'date_debut', 'date_fin', 'duree_jours')
            ->orderBy('date_debut', 'asc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'stats_mensuelles' => $statsMenuelles,
                'solde_conges' => $this->buildSoldeConges($user, $soldesParType),
                'prochains_conges' => $prochainsConges,
            ],
        ]);
    }

    private function buildSoldeConges($user, $soldesParType): array
    {
        $annuel = $this->sumLeaveTypes($soldesParType, ['conge_annuel'], $user->conges_annuels_total);
        $fractionnes = $this->sumLeaveTypes($soldesParType, ['conge_sans_solde', 'report_conge']);
        $autres = $this->sumLeaveTypes($soldesParType, [
            'conge_maladie',
            'conge_maternite',
            'conge_paternite',
            'absence_exceptionnelle',
        ]);

        return [
            'congesAnnuel' => $annuel,
            'congesFractionnes' => $fractionnes,
            'autresConges' => $autres,
            'congesPlanifies' => $annuel['enAttente'] + $fractionnes['reste'] + $autres['reste'],
        ];
    }

    private function sumLeaveTypes($soldesParType, array $types, ?int $quota = null): array
    {
        $approuves = 0;
        $enAttente = 0;

        foreach ($types as $type) {
            $row = $soldesParType->get($type);
            $approuves += (int) ($row->jours_approuves ?? 0);
            $enAttente += (int) ($row->jours_en_attente ?? 0);
        }

        if ($quota !== null) {
            return [
                'acquis' => $quota,
                'pris' => $approuves,
                'reste' => max(0, $quota - $approuves),
                'pourcentage' => $quota > 0 ? (int) round(($approuves / $quota) * 100) : 0,
                'enAttente' => $enAttente,
            ];
        }

        $total = $approuves + $enAttente;

        return [
            'acquis' => $total,
            'pris' => $approuves,
            'reste' => $enAttente,
            'pourcentage' => $total > 0 ? (int) round(($approuves / $total) * 100) : 0,
        ];
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
                'message' => 'Acces non autorise',
            ], 403);
        }

        $query = DemandeConge::query();

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
