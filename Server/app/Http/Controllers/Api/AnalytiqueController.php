<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandeConge;
use App\Models\User;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AnalytiqueController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $annee  = (int) $request->get('annee', now()->year);

        // ── Périmètre selon le rôle ──────────────────────────────────────
        $userIds = $this->getUserIds($user, $request);

        $baseQuery = fn() => DemandeConge::whereIn('user_id', $userIds)
            ->whereYear('created_at', $annee);

        // ── 1. KPIs ──────────────────────────────────────────────────────
        $total      = $baseQuery()->count();
        $approuves  = $baseQuery()->where('statut', 'approuve')->count();
        $rejetes    = $baseQuery()->where('statut', 'rejete')->count();
        $enAttente  = $baseQuery()->where('statut', 'en_attente')->count();
        $joursPris  = $baseQuery()->where('statut', 'approuve')->sum('duree_jours');
        $tauxAppro  = $total > 0 ? round(($approuves / $total) * 100) : 0;

        // ── 2. Demandes par mois (barres) ────────────────────────────────
        $parMoisRaw = $baseQuery()
            ->select(
                DB::raw('EXTRACT(MONTH FROM created_at)::int AS mois'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN statut = 'approuve' THEN 1 ELSE 0 END) as approuves"),
                DB::raw("SUM(CASE WHEN statut = 'rejete'   THEN 1 ELSE 0 END) as rejetes")
            )
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->keyBy('mois');

        $moisLabels = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
        $parMois = [];
        for ($m = 1; $m <= 12; $m++) {
            $parMois[] = [
                'mois'      => $moisLabels[$m - 1],
                'total'     => $parMoisRaw->get($m)?->total     ?? 0,
                'approuves' => $parMoisRaw->get($m)?->approuves ?? 0,
                'rejetes'   => $parMoisRaw->get($m)?->rejetes   ?? 0,
            ];
        }

        // ── 3. Répartition par type (camembert) ──────────────────────────
        $parType = $baseQuery()
            ->select('type_demande', DB::raw('COUNT(*) as total'))
            ->groupBy('type_demande')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'type'  => $r->type_demande,
                'label' => $this->typeLabel($r->type_demande),
                'total' => (int) $r->total,
            ]);

        // ── 4. Taux d'absence par département ────────────────────────────
        $departments = Department::with('users')->get();
        $parDept = $departments->map(function ($dept) use ($annee, $userIds) {
            $deptUserIds = $dept->users->pluck('id')
                ->intersect($userIds)->values();

            if ($deptUserIds->isEmpty()) return null;

            $total     = DemandeConge::whereIn('user_id', $deptUserIds)
                ->whereYear('created_at', $annee)->count();
            $approuves = DemandeConge::whereIn('user_id', $deptUserIds)
                ->whereYear('created_at', $annee)
                ->where('statut', 'approuve')->count();
            $jours     = DemandeConge::whereIn('user_id', $deptUserIds)
                ->whereYear('created_at', $annee)
                ->where('statut', 'approuve')->sum('duree_jours');

            return [
                'department'  => $dept->name,
                'nb_employes' => $deptUserIds->count(),
                'total'       => $total,
                'approuves'   => $approuves,
                'jours_pris'  => (int) $jours,
                'moy_jours'   => $deptUserIds->count() > 0
                    ? round($jours / $deptUserIds->count(), 1) : 0,
            ];
        })->filter()->values();

        // ── 5. Top employés (jours pris) ─────────────────────────────────
        $topEmployes = DemandeConge::whereIn('user_id', $userIds)
            ->whereYear('created_at', $annee)
            ->where('statut', 'approuve')
            ->select('user_id', DB::raw('SUM(duree_jours) as total_jours'), DB::raw('COUNT(*) as nb_demandes'))
            ->groupBy('user_id')
            ->orderByDesc('total_jours')
            ->limit(5)
            ->with('user:id,name,first_name,department_id')
            ->get()
            ->map(fn($r) => [
                'nom'          => $r->user?->full_name ?? '—',
                'total_jours'  => (int) $r->total_jours,
                'nb_demandes'  => (int) $r->nb_demandes,
                'departement'  => $r->user?->department?->name ?? '—',
            ]);

        // ── 6. Tendance 6 derniers mois (courbe) ─────────────────────────
        $tendance = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = DemandeConge::whereIn('user_id', $userIds)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->where('statut', 'approuve')
                ->count();
            $tendance[] = [
                'mois'  => $date->locale('fr')->translatedFormat('M y'),
                'total' => $count,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'annee'       => $annee,
                'kpis'        => compact('total','approuves','rejetes','enAttente','joursPris','tauxAppro'),
                'par_mois'    => $parMois,
                'par_type'    => $parType,
                'par_dept'    => $parDept,
                'top_employes'=> $topEmployes,
                'tendance'    => $tendance,
            ],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function getUserIds(User $user, Request $request): array
    {
        return match ($user->role->nom) {
            'Superieur'     => $user->subordinates()->pluck('id')->toArray(),
            'Responsable RH','Directeur Unité' => $user->department_id
                ? User::where('department_id', $user->department_id)->pluck('id')->toArray()
                : [$user->id],
            'Directeur RH','Admin' => $request->filled('department_id')
                ? User::where('department_id', $request->department_id)->pluck('id')->toArray()
                : User::pluck('id')->toArray(),
            default => [$user->id],
        };
    }

    private function typeLabel(string $type): string
    {
        return [
            'conge_annuel'           => 'Congé annuel',
            'conge_maladie'          => 'Congé maladie',
            'conge_maternite'        => 'Congé maternité',
            'conge_paternite'        => 'Congé paternité',
            'conge_sans_solde'       => 'Congé sans solde',
            'absence_exceptionnelle' => 'Absence exceptionnelle',
            'report_conge'           => 'Report de congé',
        ][$type] ?? $type;
    }
}