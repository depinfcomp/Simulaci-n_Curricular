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
            // Update the leveling subject
            $levelingSubject->update($validated);

            // Check if this subject also exists in the official curriculum (subjects table)
            $officialSubject = \App\Models\Subject::where('code', $levelingSubject->code)->first();
            
            if ($officialSubject) {
                // Update the official subject as well to keep them in sync
                $officialSubject->update([
                    'name' => $validated['name'],
                    'credits' => $validated['credits'],
                    'classroom_hours' => $validated['classroom_hours'] ?? $officialSubject->classroom_hours,
                    'student_hours' => $validated['student_hours'] ?? $officialSubject->student_hours,
                    'description' => $validated['description'] ?? $officialSubject->description,
                ]);
                
                Log::info('Synced leveling subject update to official curriculum', [
                    'code' => $levelingSubject->code,
                    'name' => $validated['name']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Materia de nivelación actualizada exitosamente',
                'leveling' => $levelingSubject->fresh(),
                'synced_to_curriculum' => $officialSubject ? true : false
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
