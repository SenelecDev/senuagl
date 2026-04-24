<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogger;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::with(['role', 'department'])
            ->where('email', $request->email)
            ->orWhere('matricule', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            ActivityLogger::warning('LOGIN_FAILED', "Tentative de connexion échouée pour {$request->email}", 'auth');
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects',
            ], 401);
        }

        if (!$user->is_active) {
            ActivityLogger::warning('LOGIN_BLOCKED', "Compte désactivé - tentative de connexion pour {$user->full_name}", 'auth', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => 'Compte désactivé',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLogger::success('LOGIN', "Connexion réussie pour {$user->full_name}", 'auth', ['user_id' => $user->id, 'role' => $user->role?->nom]);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'email' => $user->email,
                    'matricule' => $user->matricule,
                    'phone' => $user->phone,
                    'full_name' => $user->first_name . ' ' . $user->name,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'name' => $user->role->nom,
                        'description' => $user->role->description,
                    ] : null,
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'name' => $user->department->name,
                        'description' => $user->department->description,
                    ] : null,
                    'is_active' => $user->is_active,
                    'is_manager' => $user->isManager(),
                    'can_validate_leave' => $user->canValidateLeave(),
                ],
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        ActivityLogger::info('LOGOUT', "Déconnexion de {$user->full_name}", 'auth', ['user_id' => $user->id]);
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['role', 'department', 'manager']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'email' => $user->email,
                    'matricule' => $user->matricule,
                    'phone' => $user->phone,
                    'full_name' => $user->first_name . ' ' . $user->name,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'name' => $user->role->nom,
                        'description' => $user->role->description,
                    ] : null,
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'name' => $user->department->name,
                        'description' => $user->department->description,
                    ] : null,
                    'manager' => $user->manager ? [
                        'id' => $user->manager->id,
                        'name' => $user->manager->name,
                        'full_name' => $user->manager->first_name . ' ' . $user->manager->name,
                    ] : null,
                    'is_active' => $user->is_active,
                    'is_manager' => $user->isManager(),
                    'can_validate_leave' => $user->canValidateLeave(),
                ],
            ],
        ]);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token rafraîchi',
            'data' => ['token' => $token],
        ]);
    }
}