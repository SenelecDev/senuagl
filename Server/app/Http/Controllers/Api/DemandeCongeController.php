<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandeConge;
use App\Models\Notification;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DemandeCongeController extends Controller
{
    // ================================================================
    // RÈGLES DE CONTRÔLE
    // ================================================================

    private function verifierRegles($user, $request, $dateDebut, $dateFin, $dureeJours)
    {
        $errors = [];

        // RÈGLE 1 — Chevauchement de dates
        $chevauchement = DemandeConge::where('user_id', $user->id)
            ->whereIn('statut', ['en_attente', 'approuve'])
            ->where(function ($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('date_debut', [$dateDebut, $dateFin])
                  ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                  ->orWhere(function ($q2) use ($dateDebut, $dateFin) {
                      $q2->where('date_debut', '<=', $dateDebut)
                         ->where('date_fin', '>=', $dateFin);
                  });
            })
            ->first();

        if ($chevauchement) {
            $errors[] = "Vous avez déjà une demande ({$chevauchement->statut_label}) sur cette période : "
                . $chevauchement->date_debut->format('d/m/Y')
                . " - "
                . $chevauchement->date_fin->format('d/m/Y')
                . ". Veuillez choisir d'autres dates.";
        }

        // RÈGLE 2 — Solde insuffisant (congé annuel)
        if ($request->type_demande === 'conge_annuel' && $user->conges_annuels_restants < $dureeJours) {
            $errors[] = "Solde de congés annuels insuffisant. Vous avez {$user->conges_annuels_restants} jour(s) restant(s) mais vous demandez {$dureeJours} jour(s).";
        }

        // RÈGLE 3 — Délai minimum de soumission (3 jours ouvrables à l'avance)
        $delaiMinimum = 3;
        $joursAvant = Carbon::now()->diffInDays($dateDebut, false);
        if ($joursAvant < $delaiMinimum) {
            $errors[] = "La demande doit être soumise au moins {$delaiMinimum} jours à l'avance. Veuillez choisir une date de début après le " . Carbon::now()->addDays($delaiMinimum)->format('d/m/Y') . ".";
        }

        // RÈGLE 4 — Durée maximale par demande (30 jours)
        $dureeMax = 30;
        if ($dureeJours > $dureeMax) {
            $errors[] = "La durée maximale d'une demande est de {$dureeMax} jours. Votre demande couvre {$dureeJours} jours.";
        }

        // RÈGLE 5 — Durée minimale (1 jour)
        if ($dureeJours < 1) {
            $errors[] = "La durée minimale d'une demande est d'1 jour.";
        }

        // RÈGLE 6 — Limite annuelle pour absence exceptionnelle (3 max par an)
        if ($request->type_demande === 'absence_exceptionnelle') {
            $annee = $dateDebut->year;
            $nbAbsences = DemandeConge::where('user_id', $user->id)
                ->where('type_demande', 'absence_exceptionnelle')
                ->whereIn('statut', ['en_attente', 'approuve'])
                ->whereYear('date_debut', $annee)
                ->count();

            if ($nbAbsences >= 3) {
                $errors[] = "Vous avez atteint la limite de 3 absences exceptionnelles pour l'année {$annee}.";
            }
        }

        // RÈGLE 7 — Congé maternité/paternité : une seule fois par an
        if (in_array($request->type_demande, ['conge_maternite', 'conge_paternite'])) {
            $annee = $dateDebut->year;
            $existe = DemandeConge::where('user_id', $user->id)
                ->where('type_demande', $request->type_demande)
                ->whereIn('statut', ['en_attente', 'approuve'])
                ->whereYear('date_debut', $annee)
                ->exists();

            if ($existe) {
                $typeLabel = $request->type_demande === 'conge_maternite' ? 'maternité' : 'paternité';
                $errors[] = "Vous avez déjà une demande de congé {$typeLabel} pour l'année {$annee}.";
            }
        }

        // RÈGLE 8 — Pas de demande le week-end (date début ne doit pas être samedi ou dimanche)
        if ($dateDebut->isWeekend()) {
            $errors[] = "La date de début ne peut pas être un week-end. Veuillez choisir un jour ouvrable.";
        }

        return $errors;
    }

    // ================================================================
    // MÉTHODES CRUD
    // ================================================================

    public function index(Request $request)
    {
        $query = DemandeConge::with(['user', 'validateur'])
                             ->where('user_id', $request->user()->id);

        if ($request->has('statut')) $query->where('statut', $request->statut);
        if ($request->has('type')) $query->where('type_demande', $request->type);
        if ($request->has('date_debut')) $query->whereDate('date_debut', '>=', $request->date_debut);
        if ($request->has('date_fin')) $query->whereDate('date_fin', '<=', $request->date_fin);

        $demandes = $query->orderBy('created_at', 'desc')->paginate(10);
        return response()->json(['success' => true, 'data' => $demandes]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_demande' => 'required|in:conge_annuel,conge_maladie,conge_maternite,conge_paternite,conge_sans_solde,absence_exceptionnelle,report_conge',
            'date_debut'   => 'required|date',
            'date_fin'     => 'required|date|after_or_equal:date_debut',
            'motif'        => 'required|string|max:1000',
            'commentaire'  => 'nullable|string|max:1000',
            'signatures'   => 'nullable|array',
            'pieces_jointes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $dateDebut  = Carbon::parse($request->date_debut);
        $dateFin    = Carbon::parse($request->date_fin);
        $dureeJours = $dateDebut->diffInDays($dateFin) + 1;
        $user       = $request->user();

        // ---- Vérification de toutes les règles ----
        $errors = $this->verifierRegles($user, $request, $dateDebut, $dateFin, $dureeJours);

        if (!empty($errors)) {
            ActivityLogger::warning(
                'DEMANDE_REFUSED',
                "Demande refusée pour {$user->full_name} : " . implode(' | ', $errors),
                'demandes',
                ['user_id' => $user->id]
            );
            return response()->json([
                'success' => false,
                'message' => $errors[0], // Message principal
                'errors'  => $errors,    // Tous les messages
            ], 422);
        }

        // ---- Traitement signatures et pièces jointes ----
        $signatures = [];
        if ($request->has('signatures')) {
            foreach ($request->signatures as $type => $signatureData) {
                if ($signatureData) {
                    $signatures[$type] = $this->storeSignature($signatureData, $user->id);
                }
            }
        }

        $piecesJointes = [];
        if ($request->has('pieces_jointes')) {
            foreach ($request->pieces_jointes as $file) {
                $piecesJointes[] = $this->storeFile($file, $user->id);
            }
        }

        // ---- Création de la demande ----
        $demande = DemandeConge::create([
            'user_id'       => $user->id,
            'type_demande'  => $request->type_demande,
            'date_debut'    => $request->date_debut,
            'date_fin'      => $request->date_fin,
            'duree_jours'   => $dureeJours,
            'motif'         => $request->motif,
            'commentaire'   => $request->commentaire,
            'signatures'    => $signatures,
            'pieces_jointes' => $piecesJointes,
        ]);

        ActivityLogger::success(
            'DEMANDE_CREATED',
            "Demande de {$demande->type_label} créée par {$user->full_name} ({$dureeJours} jours)",
            'demandes',
            ['demande_id' => $demande->id, 'user_id' => $user->id]
        );

        // ---- Notification manager ----
        try {
            $manager = $user->manager()->first();
            if ($manager) {
                Notification::create([
                    'user_id' => $manager->id,
                    'titre'   => 'Nouvelle demande de congé',
                    'message' => "{$user->full_name} a soumis une demande de {$demande->type_label}",
                    'type'    => 'info',
                    'data'    => ['demande_id' => $demande->id],
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Notification manager échouée: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande créée avec succès',
            'data'    => $demande->load(['user', 'validateur']),
        ], 201);
    }

    public function show(DemandeConge $demande)
    {
        $user = auth()->user();
        if ($demande->user_id !== $user->id && !$user->canValidateLeave()) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }
        return response()->json(['success' => true, 'data' => $demande->load(['user', 'validateur'])]);
    }

    public function indexAdmin(Request $request)
    {
        $user = $request->user();
        if ($user->role->nom !== 'Admin') {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $query = DemandeConge::with(['user', 'user.department', 'validateur']);

        if ($request->has('search') && $request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('matricule', 'like', '%' . $request->search . '%');
            });
        }

        $demandes = $query->orderBy('created_at', 'desc')->paginate(10);
        return response()->json(['success' => true, 'data' => $demandes]);
    }

    public function update(Request $request, DemandeConge $demande)
    {
        $user = $request->user();
        if ($demande->user_id !== $user->id || $demande->statut !== 'en_attente') {
            return response()->json(['success' => false, 'message' => 'Impossible de modifier cette demande'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type_demande'  => 'sometimes|in:conge_annuel,conge_maladie,conge_maternite,conge_paternite,conge_sans_solde,absence_exceptionnelle,report_conge',
            'date_debut'    => 'sometimes|date',
            'date_fin'      => 'sometimes|date|after_or_equal:date_debut',
            'motif'         => 'sometimes|string|max:1000',
            'commentaire'   => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        $demande->update($request->only(['type_demande', 'date_debut', 'date_fin', 'motif', 'commentaire']));

        if ($request->has('date_debut') || $request->has('date_fin')) {
            $dateDebut = Carbon::parse($demande->date_debut);
            $dateFin   = Carbon::parse($demande->date_fin);
            $demande->duree_jours = $dateDebut->diffInDays($dateFin) + 1;
            $demande->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande mise à jour avec succès',
            'data'    => $demande->load(['user', 'validateur']),
        ]);
    }

    public function destroy(DemandeConge $demande)
    {
        $user = auth()->user();
        if ($demande->user_id !== $user->id || $demande->statut !== 'en_attente') {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer cette demande'], 403);
        }
        $demande->delete();
        ActivityLogger::info('DEMANDE_DELETED', "Demande #{$demande->id} annulée par {$user->full_name}", 'demandes', ['demande_id' => $demande->id]);
        return response()->json(['success' => true, 'message' => 'Demande supprimée avec succès']);
    }

    public function validateDemande(Request $request, DemandeConge $demande)
    {
        $user = $request->user();

        if (!$user->canValidateLeave()) {
            return response()->json(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour valider cette demande'], 403);
        }

        $validator = Validator::make($request->all(), [
            'action'      => 'required|in:approve,reject',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        $statut = $request->action === 'approve' ? 'approuve' : 'rejete';

        $demande->update([
            'statut'                  => $statut,
            'valide_par'              => $user->id,
            'date_validation'         => now(),
            'commentaire_validation'  => $request->commentaire,
        ]);

        if ($statut === 'approuve' && $demande->type_demande === 'conge_annuel') {
            $demande->user->decrement('conges_annuels_restants', $demande->duree_jours);
        }

        ActivityLogger::success(
            'DEMANDE_VALIDATED',
            "Demande #{$demande->id} {$statut} par {$user->full_name}",
            'demandes',
            ['demande_id' => $demande->id, 'statut' => $statut, 'validateur_id' => $user->id]
        );

        try {
            $demande->load('user');
            if ($demande->user_id) {
                Notification::create([
                    'user_id' => $demande->user_id,
                    'titre'   => 'Demande de congé ' . ($statut === 'approuve' ? 'approuvée' : 'rejetée'),
                    'message' => "Votre demande de {$demande->type_label} a été "
                        . ($statut === 'approuve' ? 'approuvée' : 'rejetée')
                        . " par {$user->full_name}",
                    'type' => $statut === 'approuve' ? 'success' : 'error',
                    'data' => ['demande_id' => $demande->id],
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Notification validation échouée: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande ' . ($statut === 'approuve' ? 'approuvée' : 'rejetée') . ' avec succès',
            'data'    => $demande->load(['user', 'validateur']),
        ]);
    }

    public function demandesAValider(Request $request)
    {
        $user = $request->user();

        if (!$user->canValidateLeave()) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $query = DemandeConge::with(['user', 'user.department', 'validateur']);

        if ($request->has('statut') && $request->statut !== 'tous') {
            $query->where('statut', $request->statut);
        }

        switch ($user->role->nom) {
            case 'Superieur':
                $ids = $user->subordinates->pluck('id');
                $query->whereIn('user_id', $ids);
                break;
            case 'Responsable RH':
            case 'Directeur Unité':
                if ($user->department_id) {
                    $deptUserIds = \App\Models\User::where('department_id', $user->department_id)->pluck('id');
                    $query->whereIn('user_id', $deptUserIds);
                }
                break;
            case 'Directeur RH':
            case 'Admin':
                break;
            default:
                $query->whereRaw('1 = 0');
                break;
        }

        $demandes = $query->orderBy('created_at', 'desc')->paginate(100);
        return response()->json(['success' => true, 'data' => $demandes]);
    }

    private function storeSignature($signatureData, $userId)
    {
        $image     = str_replace('data:image/png;base64,', '', $signatureData);
        $image     = str_replace(' ', '+', $image);
        $imageName = 'signature_' . $userId . '_' . time() . '.png';
        Storage::disk('public')->put('signatures/' . $imageName, base64_decode($image));
        return 'signatures/' . $imageName;
    }

    private function storeFile($file, $userId)
    {
        return 'files/' . $file;
    }
}