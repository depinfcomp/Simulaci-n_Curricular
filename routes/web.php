<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\SubjectOrderController;
use App\Http\Controllers\ConvalidationController;
use App\Http\Controllers\ImportCurriculumController;

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
    
    // Curriculum versions routes
    Route::get('/simulation/versions', [SimulationController::class, 'getVersions'])->name('simulation.versions');
    Route::post('/simulation/versions/save', [SimulationController::class, 'saveVersion'])->name('simulation.versions.save');
    Route::get('/simulation/versions/{id}', [SimulationController::class, 'getVersion'])->name('simulation.versions.show');

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
        
        // Route to get total credits from external curriculum
        Route::get('/{externalCurriculum}/total-credits', [ConvalidationController::class, 'getTotalCredits'])->name('convalidation.total-credits');
        
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

    // Import curriculum wizard routes
    Route::prefix('import-curriculum')->name('import.')->group(function () {
        Route::get('/', [ImportCurriculumController::class, 'index'])->name('index');
        Route::post('/upload', [ImportCurriculumController::class, 'upload'])->name('upload');
        Route::post('/{import}/analyze', [ImportCurriculumController::class, 'analyze'])->name('analyze');
        Route::post('/{import}/mapping', [ImportCurriculumController::class, 'updateMapping'])->name('mapping');
        Route::post('/{import}/validate', [ImportCurriculumController::class, 'validateData'])->name('validate');
        Route::post('/{import}/fill', [ImportCurriculumController::class, 'updateMissingData'])->name('fill');
        Route::post('/{import}/confirm', [ImportCurriculumController::class, 'confirm'])->name('confirm');
        Route::get('/{import}/status', [ImportCurriculumController::class, 'status'])->name('status');
        Route::get('/templates', [ImportCurriculumController::class, 'templates'])->name('templates');
        Route::post('/{import}/apply-template', [ImportCurriculumController::class, 'applyTemplate'])->name('apply-template');
    });
});

// Authentication routes
require __DIR__.'/auth.php';
