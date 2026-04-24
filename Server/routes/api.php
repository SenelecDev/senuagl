<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DemandeCongeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DepartmentController;
//use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ActivityLogController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {

    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);
    Route::get('/dashboard/stats-manager', [DashboardController::class, 'statsManager']);
    Route::get('/admin/activity-logs', [ActivityLogController::class, 'index']);
    // Demandes de congés
    Route::apiResource('demandes-conges', DemandeCongeController::class);
Route::post('/demandes-conges/{id}/validate', function(\Illuminate\Http\Request $request, $id) {
    $demande = \App\Models\DemandeConge::findOrFail($id);
    return app(\App\Http\Controllers\Api\DemandeCongeController::class)->validateDemande($request, $demande);
});    Route::get('/demandes-a-valider', [DemandeCongeController::class, 'demandesAValider']);

    // Gestion des utilisateurs (Admin)
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::get('/managers', [UserController::class, 'getManagers']);
//    localhost:8000/users?page=1&limit=10
    // Gestion des rôles
    Route::apiResource('roles', RoleController::class);

    // Gestion des départements
    Route::apiResource('departments', DepartmentController::class);
    Route::get('/departments/{department}/stats', [DepartmentController::class, 'stats']);
    Route::get('/admin/demandes', [DemandeCongeController::class, 'indexAdmin']);
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    //Route::get('/admin/activity-logs', [ActivityLogController::class, 'index']);
});

// Route de test
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API Laravel fonctionne correctement',
        'timestamp' => now(),
    ]);
});

// Routes pour la documentation API
Route::get('/openapi.json', [App\Http\Controllers\ApiDocController::class, 'openapi']);
Route::get('/health', [App\Http\Controllers\ApiDocController::class, 'health']);
 