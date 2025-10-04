<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\SubjectOrderController;
use App\Http\Controllers\ConvalidationController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Protected routes - require authentication and password change check
Route::middleware(['auth', \App\Http\Middleware\CheckMustChangePassword::class])->group(function () {
    // Simulation routes
    Route::get('/simulation', function () {
        return view('simulation.index');
    })->name('simulation.index');
    
    Route::post('/simulation/analyze-impact', [SimulationController::class, 'analyzeImpact'])->name('simulation.analyzeImpact');
    Route::get('/simulation/original-order', [SubjectOrderController::class, 'getOriginalOrderJson'])->name('simulation.originalOrder');

    // Convalidation routes
    Route::group(['prefix' => 'convalidation'], function () {
        Route::get('/', [ConvalidationController::class, 'index'])->name('convalidation.index');
        Route::get('/create', [ConvalidationController::class, 'create'])->name('convalidation.create');
        Route::post('/', [ConvalidationController::class, 'store'])->name('convalidation.store');
        Route::get('/{externalCurriculum}', [ConvalidationController::class, 'show'])->name('convalidation.show');
        Route::delete('/{externalCurriculum}', [ConvalidationController::class, 'destroy'])->name('convalidation.destroy');
        Route::get('/{externalCurriculum}/export', [ConvalidationController::class, 'exportReport'])->name('convalidation.export');
        
        // New route for impact analysis
        Route::post('/{externalCurriculum}/analyze-impact', [ConvalidationController::class, 'analyzeConvalidationImpact'])->name('convalidation.analyze-impact');
        
        // New route for convalidations summary
        Route::get('/{externalCurriculum}/convalidations-summary', [ConvalidationController::class, 'getConvalidationsSummary'])->name('convalidation.summary');
        
        // Test route for debugging
        Route::post('/test-endpoint', [ConvalidationController::class, 'testEndpoint'])->name('convalidation.test');
        
        // Debug route for convalidation matching - TEMPORAL, REMOVE IN PRODUCTION
        Route::get('/{externalCurriculum}/debug-matching', [ConvalidationController::class, 'debugConvalidationMatching'])->name('convalidation.debug-matching');
        
        // Convalidation management
        Route::post('/convalidation', [ConvalidationController::class, 'storeConvalidation'])->name('convalidation.store-convalidation');
        Route::delete('/convalidation/{convalidation}', [ConvalidationController::class, 'destroyConvalidation'])->name('convalidation.destroy-convalidation');
        Route::get('/suggestions', [ConvalidationController::class, 'getSuggestions'])->name('convalidation.suggestions');
        
        // Save modified curriculum from simulation
        Route::post('/save-modified-curriculum', [ConvalidationController::class, 'saveModifiedCurriculum'])->name('convalidation.save-modified-curriculum');
    });
});

// Authentication routes
require __DIR__.'/auth.php';
