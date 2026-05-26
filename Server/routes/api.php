<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvancementController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvestissementController;
use App\Http\Controllers\Api\NoteAppreciationController;
use App\Http\Controllers\Api\PosteController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\StatistiqueController;
use App\Http\Controllers\Api\UniteController;
use App\Models\GF;
use App\Models\NR;
use Illuminate\Support\Facades\Route;

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
        $gfs = GF::all();

        return response()->json($gfs);
    });

    Route::get('nrs', function () {
        $nrs = NR::all();

        return response()->json($nrs);
    });

    // Statistiques
    Route::get('statistiques/pyramide-ages', [StatistiqueController::class, 'pyramideAges']);
    Route::get('statistiques/repartition-hf', [StatistiqueController::class, 'repartitionHF']);
    Route::get('statistiques/repartition-hf-par-service', [StatistiqueController::class, 'repartitionHFParService']);
    Route::get('statistiques/departs-retraite', [StatistiqueController::class, 'departsRetraite']);
    Route::get('statistiques/plafonnement-anomalies', [StatistiqueController::class, 'plafonnementAnomalies']);
    Route::get('statistiques/agents-plafonnes', [StatistiqueController::class, 'agentsPlafonnes']);

    // Promotions & Avancements (routes spéciales AVANT apiResource)
    Route::get('promotions/liste-priorite-gf', [PromotionController::class, 'listePrioriteGFTous']);
    Route::get('avancements/liste-priorite-nr', [PromotionController::class, 'listePrioriteNRTous']);
    Route::get('promotions/liste-priorite-gf/{directionId}/{annee}', [PromotionController::class, 'listePrioriteGF']);
    Route::get('avancements/liste-priorite-nr/{directionId}/{annee}', [PromotionController::class, 'listePrioriteNR']);
    Route::post('promotions/promouvoir', [PromotionController::class, 'promouvoir']);
    Route::post('avancements/avancer', [PromotionController::class, 'avancer']);

    // Avancements
    Route::apiResource('avancements', AvancementController::class);

    // Notes d'appréciation
    Route::apiResource('notes-appreciation', NoteAppreciationController::class);
    Route::get('notes-appreciation/agent/{matricule}', [NoteAppreciationController::class, 'getByAgent']);
    Route::get('notes-appreciation/annee/{annee}', [NoteAppreciationController::class, 'getByAnnee']);

    // Dashboard
    Route::get('/dashboard/kpi', [DashboardController::class, 'kpi']);

    // Budget & investissements
    Route::get('budget/referentiels', [BudgetController::class, 'referentiels']);
    Route::get('budget', [BudgetController::class, 'index']);
    Route::post('budget', [BudgetController::class, 'store']);
    Route::put('budget/{type}/{id}', [BudgetController::class, 'update'])
        ->whereIn('type', ['prevision', 'realisation']);
    Route::delete('budget/{type}/{id}', [BudgetController::class, 'destroy'])
        ->whereIn('type', ['prevision', 'realisation']);

    Route::get('investissements', [InvestissementController::class, 'index']);
    Route::post('investissements', [InvestissementController::class, 'store']);
    Route::post('investissements/calculate', [InvestissementController::class, 'calculate']);

});
