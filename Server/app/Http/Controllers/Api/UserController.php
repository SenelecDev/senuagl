<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'department', 'manager']);

        if ($request->has('role_id') && $request->role_id) $query->where('role_id', $request->role_id);
        if ($request->has('department_id') && $request->department_id) $query->where('department_id', $request->department_id);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) $query->where('is_active', $request->boolean('is_active'));

        $perPage = $request->input('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'matricule' => 'required|string|max:50|unique:users',
            'password' => 'required|string|min:6',
            'telephone' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'manager_id' => 'nullable|exists:users,id',
            'conges_annuels_total' => 'nullable|integer|min:0|max:60',
            'date_embauche' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        $userData = $validator->validated();

        $mappedUserData = [
            'name' => $userData['nom'],
            'first_name' => $userData['prenom'],
            'email' => $userData['email'],
            'matricule' => $userData['matricule'],
            'password' => Hash::make($userData['password']),
            'phone' => $userData['telephone'] ?? null,
            'department_id' => $userData['department_id'] ?? null,
            'role_id' => $userData['role_id'],
            'manager_id' => $userData['manager_id'] ?? null,
            'date_embauche' => $userData['date_embauche'] ?? null,
            'conges_annuels_total' => $userData['conges_annuels_total'] ?? 30,
        ];
        $mappedUserData['conges_annuels_restants'] = $mappedUserData['conges_annuels_total'];

        $user = User::create($mappedUserData);
        $user->load(['role', 'department', 'manager']);

        ActivityLogger::success('USER_CREATED', "Utilisateur {$user->full_name} ({$user->role?->nom}) créé", 'users', ['user_id' => $user->id, 'role' => $user->role?->nom]);

        return response()->json(['success' => true, 'message' => 'Utilisateur créé avec succès', 'data' => $user], 201);
    }

    public function show(User $user)
    {
        $user->load(['role', 'department', 'manager', 'subordinates']);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'matricule' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'telephone' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'manager_id' => 'nullable|exists:users,id',
            'conges_annuels_total' => 'nullable|integer|min:0|max:60',
            'conges_annuels_restants' => 'nullable|integer|min:0',
            'date_embauche' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        $userData = $validator->validated();

        if (!empty($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            unset($userData['password']);
        }

        $user->update($userData);
        $user->load(['role', 'department', 'manager']);

        ActivityLogger::info('USER_UPDATED', "Utilisateur {$user->full_name} modifié", 'users', ['user_id' => $user->id]);

        return response()->json(['success' => true, 'message' => 'Utilisateur mis à jour avec succès', 'data' => $user]);
    }

    public function destroy(User $user)
    {
        $fullName = $user->full_name;
        $userId = $user->id;
        $user->delete();
        ActivityLogger::warning('USER_DELETED', "Utilisateur {$fullName} supprimé", 'users', ['user_id' => $userId]);
        return response()->json(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
    }

    public function getManagers()
    {
        $managers = User::with(['role', 'department'])
            ->whereHas('role', function ($query) {
                $query->whereIn('nom', ['Directeur RH', 'Responsable RH', 'Directeur Unité', 'Superieur']);
            })
            ->where('is_active', true)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->first_name . ' ' . $user->name,
                    'role' => $user->role->nom,
                    'department' => $user->department?->name ?? 'N/A',
                ];
            });

        return response()->json(['success' => true, 'data' => $managers]);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activé' : 'désactivé';
        ActivityLogger::info('USER_STATUS_CHANGED', "Compte de {$user->full_name} {$status}", 'users', ['user_id' => $user->id, 'is_active' => $user->is_active]);

        return response()->json(['success' => true, 'message' => "Utilisateur {$status}", 'data' => $user]);
    }

    public function resetPassword(Request $request, User $user)
    {
        if ($request->has('password')) {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
            }
            $newPassword = $request->password;
        } else {
            $newPassword = 'temp' . rand(1000, 9999);
        }

        $user->update(['password' => Hash::make($newPassword)]);
        ActivityLogger::info('PASSWORD_RESET', "Mot de passe de {$user->full_name} réinitialisé", 'users', ['user_id' => $user->id]);

        $response = ['success' => true, 'message' => 'Mot de passe réinitialisé avec succès'];
        if (!$request->has('password')) {
            $response['data'] = ['new_password' => $newPassword];
        }

        return response()->json($response);
    }
}