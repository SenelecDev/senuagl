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
            'date_debut' => 'required|date|after:today',
            'date_fin' => 'required|date|after:date_debut',
            'motif' => 'required|string|max:1000',
            'commentaire' => 'nullable|string|max:1000',
            'signatures' => 'nullable|array',
            'pieces_jointes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dateDebut = Carbon::parse($request->date_debut);
        $dateFin = Carbon::parse($request->date_fin);
        $dureeJours = $dateDebut->diffInDays($dateFin) + 1;

        $user = $request->user();

        if ($request->type_demande === 'conge_annuel' && $user->conges_annuels_restants < $dureeJours) {
            ActivityLogger::warning('DEMANDE_REFUSED', "Solde insuffisant pour {$user->full_name} ({$dureeJours}j demandés, {$user->conges_annuels_restants}j restants)", 'demandes', ['user_id' => $user->id]);
            return response()->json(['success' => false, 'message' => 'Solde de congés insuffisant'], 400);
        }

        $signatures = [];
        if ($request->has('signatures')) {
            foreach ($request->signatures as $type => $signatureData) {
                if ($signatureData) $signatures[$type] = $this->storeSignature($signatureData, $user->id);
            }
        }

        $piecesJointes = [];
        if ($request->has('pieces_jointes')) {
            foreach ($request->pieces_jointes as $file) {
                $piecesJointes[] = $this->storeFile($file, $user->id);
            }
        }

        $demande = DemandeConge::create([
            'user_id' => $user->id,
            'type_demande' => $request->type_demande,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'duree_jours' => $dureeJours,
            'motif' => $request->motif,
            'commentaire' => $request->commentaire,
            'signatures' => $signatures,
            'pieces_jointes' => $piecesJointes,
        ]);

        ActivityLogger::success('DEMANDE_CREATED', "Demande de {$demande->type_label} créée par {$user->full_name} ({$dureeJours} jours)", 'demandes', ['demande_id' => $demande->id, 'user_id' => $user->id]);

        try {
            $manager = $user->manager()->first();
            if ($manager) {
                Notification::create([
                    'user_id' => $manager->id,
                    'titre' => 'Nouvelle demande de congé',
                    'message' => "{$user->full_name} a soumis une demande de {$demande->type_label}",
                    'type' => 'info',
                    'data' => ['demande_id' => $demande->id],
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Notification manager échouée: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande créée avec succès',
            'data' => $demande->load(['user', 'validateur']),
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
            'type_demande' => 'sometimes|in:conge_annuel,conge_maladie,conge_maternite,conge_paternite,conge_sans_solde,absence_exceptionnelle,report_conge',
            'date_debut' => 'sometimes|date|after:today',
            'date_fin' => 'sometimes|date|after:date_debut',
            'motif' => 'sometimes|string|max:1000',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        $demande->update($request->only(['type_demande', 'date_debut', 'date_fin', 'motif', 'commentaire']));

        if ($request->has('date_debut') || $request->has('date_fin')) {
            $dateDebut = Carbon::parse($demande->date_debut);
            $dateFin = Carbon::parse($demande->date_fin);
            $demande->duree_jours = $dateDebut->diffInDays($dateFin) + 1;
            $demande->save();
        }

        return response()->json(['success' => true, 'message' => 'Demande mise à jour avec succès', 'data' => $demande->load(['user', 'validateur'])]);
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
            'action' => 'required|in:approve,reject',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        $statut = $request->action === 'approve' ? 'approuve' : 'rejete';

        $demande->update([
            'statut' => $statut,
            'valide_par' => $user->id,
            'date_validation' => now(),
            'commentaire_validation' => $request->commentaire,
        ]);

        if ($statut === 'approuve' && $demande->type_demande === 'conge_annuel') {
            $demande->user->decrement('conges_annuels_restants', $demande->duree_jours);
        }

        ActivityLogger::success('DEMANDE_VALIDATED', "Demande #{$demande->id} {$statut} par {$user->full_name}", 'demandes', ['demande_id' => $demande->id, 'statut' => $statut, 'validateur_id' => $user->id]);

        try {
            $demande->load('user');
            if ($demande->user_id) {
                Notification::create([
                    'user_id' => $demande->user_id,
                    'titre' => 'Demande de congé ' . ($statut === 'approuve' ? 'approuvée' : 'rejetée'),
                    'message' => "Votre demande de {$demande->type_label} a été " . ($statut === 'approuve' ? 'approuvée' : 'rejetée') . " par {$user->full_name}",
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
            'data' => $demande->load(['user', 'validateur']),
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
        $image = str_replace('data:image/png;base64,', '', $signatureData);
        $image = str_replace(' ', '+', $image);
        $imageName = 'signature_' . $userId . '_' . time() . '.png';
        Storage::disk('public')->put('signatures/' . $imageName, base64_decode($image));
        return 'signatures/' . $imageName;
    }

    private function storeFile($file, $userId)
    {
        return 'files/' . $file;
    }
}