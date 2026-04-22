<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    /**
     * Récupérer tous les départements
     */
    public function index(): JsonResponse
    {
        try {
            $departments = Department::with(['manager'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $departments,
                'message' => 'Départements récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des départements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau département
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
    'name' => 'required|string|max:255|unique:departments,name',
    'description' => 'nullable|string',
    'manager_id' => 'nullable|exists:users,id',
    'budget' => 'nullable|numeric|min:0',
]);

// Générer un code unique automatiquement
$baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 6));
$code = $baseCode;
$counter = 1;
while (Department::where('code', $code)->exists()) {
    $code = $baseCode . $counter;
    $counter++;
}
$validated['code'] = $code;

$department = Department::create($validated);
            $department->load('manager');

            return response()->json([
                'success' => true,
                'data' => $department,
                'message' => 'Département créé avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du département',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un département spécifique
     */
    public function show(Department $department): JsonResponse
    {
        try {
            $department->load(['manager', 'users']);

            return response()->json([
                'success' => true,
                'data' => $department,
                'message' => 'Département récupéré avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du département',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un département
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
                'description' => 'nullable|string',
                'manager_id' => 'nullable|exists:users,id',
                'budget' => 'nullable|numeric|min:0',
            ]);

            $department->update($validated);
            $department->load('manager');

            return response()->json([
                'success' => true,
                'data' => $department,
                'message' => 'Département mis à jour avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du département',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un département
     */
    public function destroy(Department $department): JsonResponse
    {
        try {
            // Vérifier si le département a des utilisateurs
            if ($department->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce département car il contient des utilisateurs'
                ], 400);
            }

            $department->delete();

            return response()->json([
                'success' => true,
                'message' => 'Département supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du département',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques d'un département
     */
    public function stats(Department $department): JsonResponse
    {
        try {
            $stats = [
                'total_employees' => $department->users()->count(),
                'active_employees' => $department->users()->where('is_active', true)->count(),
                'inactive_employees' => $department->users()->where('is_active', false)->count(),
                'total_leave_requests' => $department->users()
                    ->withCount('demandesConges')
                    ->get()
                    ->sum('demandes_conges_count'),
                'pending_leave_requests' => $department->users()
                    ->with(['demandesConges' => function($query) {
                        $query->where('statut', 'en_attente');
                    }])
                    ->get()
                    ->sum(function($user) {
                        return $user->demandesConges->count();
                    }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques du département récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
