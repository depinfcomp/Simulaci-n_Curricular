<?php

namespace App\Http\Controllers;

use App\Models\ElectiveSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElectiveSubjectController extends Controller
{
    /**
     * Display a listing of the elective subjects.
     */
    public function index()
    {
        $fundamentalElectives = ElectiveSubject::fundamental()
            ->orderBy('semester')
            ->orderBy('name')
            ->get();

        $professionalElectives = ElectiveSubject::professional()
            ->orderBy('semester')
            ->orderBy('name')
            ->get();

        $stats = ElectiveSubject::getStats();

        return view('elective-subjects.index', compact(
            'fundamentalElectives',
            'professionalElectives',
            'stats'
        ));
    }

    /**
     * Store a newly created elective subject in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(ElectiveSubject::validationRules());

        try {
            $elective = ElectiveSubject::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Materia optativa creada exitosamente',
                'elective' => $elective
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la materia optativa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified elective subject.
     */
    public function show(ElectiveSubject $electiveSubject)
    {
        return response()->json([
            'success' => true,
            'elective' => $electiveSubject
        ]);
    }

    /**
     * Update the specified elective subject in storage.
     */
    public function update(Request $request, ElectiveSubject $electiveSubject)
    {
        $validated = $request->validate(ElectiveSubject::validationRules($electiveSubject->id));

        try {
            $electiveSubject->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Materia optativa actualizada exitosamente',
                'elective' => $electiveSubject->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la materia optativa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified elective subject from storage.
     */
    public function destroy(ElectiveSubject $electiveSubject)
    {
        try {
            $name = $electiveSubject->name;
            $electiveSubject->delete();

            return response()->json([
                'success' => true,
                'message' => "Materia optativa '{$name}' eliminada exitosamente"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la materia optativa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle active status of an elective subject.
     */
    public function toggleStatus(ElectiveSubject $electiveSubject)
    {
        try {
            $electiveSubject->update([
                'is_active' => !$electiveSubject->is_active
            ]);

            $status = $electiveSubject->is_active ? 'activada' : 'desactivada';

            return response()->json([
                'success' => true,
                'message' => "Materia optativa {$status} exitosamente",
                'is_active' => $electiveSubject->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }
}
