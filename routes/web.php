<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\SubjectOrderController;
use App\Http\Controllers\ConvalidationController;
use App\Http\Controllers\ImportCurriculumController;
use App\Http\Controllers\AcademicHistoryController;
use App\Http\Controllers\ElectiveSubjectController;
use App\Http\Controllers\LevelingSubjectController;
use App\Http\Controllers\SubjectAliasController;

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
    Route::delete('/simulation/versions/{id}', [SimulationController::class, 'deleteVersion'])->name('simulation.versions.delete');

    // Convalidation routes
    Route::group(['prefix' => 'convalidation'], function () {
        Route::get('/', [ConvalidationController::class, 'index'])->name('convalidation.index');
        Route::get('/create', [ConvalidationController::class, 'create'])->name('convalidation.create');
        Route::post('/', [ConvalidationController::class, 'store'])->name('convalidation.store');
        Route::get('/{externalCurriculum}', [ConvalidationController::class, 'show'])->name('convalidation.show');
        Route::delete('/{externalCurriculum}', [ConvalidationController::class, 'destroy'])->name('convalidation.destroy');
        Route::delete('/{externalCurriculum}/reset-simulation', [ConvalidationController::class, 'destroyAndResetSimulation'])->name('convalidation.destroy-reset');
        Route::get('/{externalCurriculum}/export', [ConvalidationController::class, 'exportReport'])->name('convalidation.export');
        
        // Download PDF report generated during simulation save
        Route::get('/{externalCurriculum}/pdf/download', [ConvalidationController::class, 'downloadPdfReport'])->name('convalidation.pdf.download');
        
        // Reset all convalidations for a curriculum
        Route::post('/{externalCurriculum}/reset', [ConvalidationController::class, 'resetConvalidations'])->name('convalidation.reset');
        
        // Get subjects already used for a component type
        Route::get('/{externalCurriculum}/used-subjects', [ConvalidationController::class, 'getUsedSubjects'])->name('convalidation.used-subjects');
        
        // Get subjects used ONLY as optativas/free electives (to block them globally)
        Route::get('/{externalCurriculum}/used-optatives-and-free', [ConvalidationController::class, 'getUsedOptativesAndFree'])->name('convalidation.used-optatives-free');
        
        // Routes for impact analysis (GET for viewing, POST for custom parameters)
        Route::get('/{externalCurriculum}/analyze-impact', [ConvalidationController::class, 'analyzeConvalidationImpact'])->name('convalidation.analyze-impact');
        Route::post('/{externalCurriculum}/analyze-impact', [ConvalidationController::class, 'analyzeConvalidationImpact']);
        
        // Route to generate PDF report of impact analysis
        Route::post('/{externalCurriculum}/impact-report-pdf', [ConvalidationController::class, 'generateImpactReportPdf'])->name('convalidation.impact-report-pdf');
        
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
        
        // Bulk convalidation
        Route::post('/bulk-convalidation', [ConvalidationController::class, 'bulkConvalidation'])->name('convalidation.bulk-convalidation');
        
        // Component assignment routes
        Route::get('/{externalCurriculum}/assign-components', [ConvalidationController::class, 'assignComponents'])->name('convalidation.assign-components');
        Route::post('/component-assignment', [ConvalidationController::class, 'storeComponentAssignment'])->name('convalidation.store-component');
        
        // N:N Convalidation Groups (one external subject = multiple internal subjects)
        Route::get('/{externalCurriculum}/groups', [ConvalidationController::class, 'getConvalidationGroups'])->name('convalidation.groups.index');
        Route::post('/groups', [ConvalidationController::class, 'storeConvalidationGroup'])->name('convalidation.groups.store');
        Route::put('/groups/{group}', [ConvalidationController::class, 'updateConvalidationGroup'])->name('convalidation.groups.update');
        Route::delete('/groups/{group}', [ConvalidationController::class, 'destroyConvalidationGroup'])->name('convalidation.groups.destroy');
        Route::get('/{externalCurriculum}/original-state', [ConvalidationController::class, 'getOriginalCurriculumState'])->name('convalidation.original-state');
        
        // Simulation routes
        Route::get('/{externalCurriculum}/simulation-analysis', [ConvalidationController::class, 'showSimulationAnalysis'])->name('convalidation.simulation-analysis');
        Route::post('/simulation/create', [ConvalidationController::class, 'createSimulation'])->name('convalidation.simulation.create');
        Route::put('/simulation/{simulation}/leveling', [ConvalidationController::class, 'updateLevelingCredits'])->name('convalidation.simulation.update-leveling');
        
        // Save modified curriculum from simulation
        Route::post('/save-modified-curriculum', [ConvalidationController::class, 'saveModifiedCurriculum'])->name('convalidation.save-modified-curriculum');
        
        // Delete multiple convalidations by IDs (for reset functionality)
        Route::post('/delete-multiple', [ConvalidationController::class, 'deleteMultipleConvalidations'])->name('convalidation.delete-multiple');
    });
    
    // API routes for internal subjects (used by N:N groups)
    Route::get('/api/subjects/all', [ConvalidationController::class, 'getInternalSubjectsForApi'])->name('api.subjects.all');

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

    // Academic History Import routes
    Route::prefix('academic-history')->name('academic-history.')->group(function () {
        Route::get('/', [AcademicHistoryController::class, 'index'])->name('index');
        Route::post('/upload', [AcademicHistoryController::class, 'upload'])->name('upload');
        Route::post('/clear-all', [AcademicHistoryController::class, 'clearAll'])->name('clear-all');
        Route::get('/{import}/preview', [AcademicHistoryController::class, 'preview'])->name('preview');
        Route::post('/{import}/mapping', [AcademicHistoryController::class, 'updateMapping'])->name('mapping');
        Route::post('/{import}/process', [AcademicHistoryController::class, 'process'])->name('process');
        Route::get('/{import}', [AcademicHistoryController::class, 'show'])->name('show');
        Route::delete('/{import}', [AcademicHistoryController::class, 'destroy'])->name('destroy');
        Route::get('/{import}/export', [AcademicHistoryController::class, 'export'])->name('export');
        Route::get('/{import}/export-successful', [AcademicHistoryController::class, 'exportSuccessful'])->name('export-successful');
        Route::get('/{import}/export-failed', [AcademicHistoryController::class, 'exportFailed'])->name('export-failed');
    });

    // Elective Subjects routes
    Route::prefix('elective-subjects')->name('elective-subjects.')->group(function () {
        Route::get('/', [ElectiveSubjectController::class, 'index'])->name('index');
        Route::post('/', [ElectiveSubjectController::class, 'store'])->name('store');
        Route::get('/{electiveSubject}', [ElectiveSubjectController::class, 'show'])->name('show');
        Route::put('/{electiveSubject}', [ElectiveSubjectController::class, 'update'])->name('update');
        Route::delete('/{electiveSubject}', [ElectiveSubjectController::class, 'destroy'])->name('destroy');
        Route::post('/{electiveSubject}/toggle-status', [ElectiveSubjectController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Leveling Subjects routes
    Route::prefix('leveling-subjects')->name('leveling-subjects.')->group(function () {
        Route::get('/', [LevelingSubjectController::class, 'index'])->name('index');
        Route::post('/', [LevelingSubjectController::class, 'store'])->name('store');
        Route::get('/{levelingSubject}', [LevelingSubjectController::class, 'show'])->name('show');
        Route::put('/{levelingSubject}', [LevelingSubjectController::class, 'update'])->name('update');
        Route::put('/update-by-code/{code}', [LevelingSubjectController::class, 'updateByCode'])->name('update-by-code');
        Route::delete('/{levelingSubject}', [LevelingSubjectController::class, 'destroy'])->name('destroy');
    });

    // Subject Aliases routes
    Route::prefix('subject-aliases')->name('subject-aliases.')->group(function () {
        Route::get('/', [SubjectAliasController::class, 'index'])->name('index');
        Route::post('/', [SubjectAliasController::class, 'store'])->name('store');
        Route::delete('/{alias}', [SubjectAliasController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-import', [SubjectAliasController::class, 'bulkImport'])->name('bulk-import');
        Route::get('/get-aliases', [SubjectAliasController::class, 'getAliases'])->name('get-aliases');
    });
});

// Authentication routes
require __DIR__.'/auth.php';
