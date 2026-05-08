<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StatistiqueController;
use App\Http\Controllers\Api\AvancementController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\UniteController;
use App\Http\Controllers\Api\PosteController;
use App\Http\Controllers\Api\DashboardController;

Route::get('/test', function () {
    return response()->json(['message' => 'API OK']);
});

// Health check endpoint (no auth required)
Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'timestamp' => now()], 200);
});

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Agents
    Route::apiResource('agents', AgentController::class);
    Route::get('agents/{matricule}/avancements', [AvancementController::class, 'getByAgent']);

    // Unites
    Route::apiResource('unites', UniteController::class);

    // Postes
    Route::apiResource('postes', PosteController::class);
    Route::get('postes-vacants', [PosteController::class, 'postesVacants']);
    Route::get('postes-arbre', [PosteController::class, 'getArbrePostes']);

    // GF et NR (lookup tables)
    Route::get('gfs', function () {
        $gfs = \App\Models\GF::all();
        return response()->json($gfs);
    });

    Route::get('nrs', function () {
        $nrs = \App\Models\NR::all();
        return response()->json($nrs);
    });

    // Statistiques
    Route::get('statistiques/pyramide-ages', [StatistiqueController::class, 'pyramideAges']);
    Route::get('statistiques/repartition-hf', [StatistiqueController::class, 'repartitionHF']);
    Route::get('statistiques/repartition-hf-par-service', [StatistiqueController::class, 'repartitionHFParService']);
    Route::get('statistiques/departs-retraite', [StatistiqueController::class, 'departsRetraite']);
    Route::get('statistiques/plafonnement-anomalies', [StatistiqueController::class, 'plafonnementAnomalies']);
    Route::get('statistiques/agents-bloques', [StatistiqueController::class, 'agentsBloques']);

    // Avancements
    Route::apiResource('avancements', AvancementController::class);

    // Dashboard
    Route::get('/dashboard/kpi', [DashboardController::class, 'kpi']);

});
