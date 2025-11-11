<?php

namespace App\Http\Controllers;

use App\Models\LevelingSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LevelingSubjectController extends Controller
{
    /**
     * Display a listing of the leveling subjects.
     */
    public function index()
    {
        $levelingSubjects = LevelingSubject::orderBy('code')->get();

        // Check which leveling subjects are also in the official curriculum (subjects table)
        $officialCodes = Subject::whereIn('code', $levelingSubjects->pluck('code'))->pluck('code')->toArray();
        
        // Add a flag to each leveling subject
        $levelingSubjects->each(function($subject) use ($officialCodes) {
            $subject->is_in_official_curriculum = in_array($subject->code, $officialCodes);
        });

        $stats = [
            'total' => LevelingSubject::count(),
            'total_credits' => LevelingSubject::sum('credits'),
            'in_curriculum' => count($officialCodes),
        ];

        return view('leveling-subjects.index', compact('levelingSubjects', 'stats'));
    }

    /**
     * Store a newly created leveling subject in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            LevelingSubject::validationRules(),
            LevelingSubject::validationMessages()
        );

        try {
            $leveling = LevelingSubject::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Materia de nivelación creada exitosamente',
                'leveling' => $leveling
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating leveling subject', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la materia de nivelación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified leveling subject.
     */
    public function show(LevelingSubject $levelingSubject)
    {
        return response()->json([
            'success' => true,
            'leveling' => $levelingSubject
        ]);
    }

    /**
     * Update the specified leveling subject in storage.
     */
    public function update(Request $request, LevelingSubject $levelingSubject)
    {
        $validated = $request->validate(
            LevelingSubject::validationRules($levelingSubject->id),
            LevelingSubject::validationMessages()
        );

        try {
            $levelingSubject->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Materia de nivelación actualizada exitosamente',
                'leveling' => $levelingSubject->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating leveling subject', [
                'error' => $e->getMessage(),
                'id' => $levelingSubject->id,
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la materia de nivelación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified leveling subject from storage.
     */
    public function destroy(LevelingSubject $levelingSubject)
    {
        try {
            $name = $levelingSubject->name;
            $levelingSubject->delete();

            return response()->json([
                'success' => true,
                'message' => "Materia de nivelación '{$name}' eliminada exitosamente"
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting leveling subject', [
                'error' => $e->getMessage(),
                'id' => $levelingSubject->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la materia de nivelación: ' . $e->getMessage()
            ], 500);
        }
    }
}
