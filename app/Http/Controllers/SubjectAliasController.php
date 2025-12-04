<?php

namespace App\Http\Controllers;

use App\Models\SubjectAlias;
use App\Models\Subject;
use App\Models\ElectiveSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubjectAliasController extends Controller
{
    /**
     * Display the subject aliases management page
     */
    public function index()
    {
        // Get all OBLIGATORY subjects (fundamental + professional required)
        $obligatorySubjects = Subject::where('is_required', true)
            ->orderBy('type')
            ->orderBy('code')
            ->get();
        
        // Get all ELECTIVE subjects (both types)
        $electiveSubjects = ElectiveSubject::orderBy('elective_type')
            ->orderBy('code')
            ->get();
        
        // Get all aliases grouped by subject
        $aliases = SubjectAlias::all()->groupBy('subject_code');
        
        return view('subject-aliases.index', compact('obligatorySubjects', 'electiveSubjects', 'aliases'));
    }

    /**
     * Store a new alias for a subject
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|string',
            'alias_code' => 'required|string',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            // Check if alias already exists
            $existing = SubjectAlias::where('alias_code', $request->alias_code)->first();
            
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => "El código alias '{$request->alias_code}' ya existe para la materia '{$existing->subject_code}'"
                ], 422);
            }

            // Check if trying to create an alias of the subject to itself
            if ($request->subject_code === $request->alias_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código alias no puede ser igual al código principal'
                ], 422);
            }

            $alias = SubjectAlias::create([
                'subject_code' => $request->subject_code,
                'alias_code' => $request->alias_code,
                'description' => $request->description
            ]);

            // Get subject info for response
            $subject = DB::table('subjects')->where('code', $request->subject_code)->first();
            if (!$subject) {
                $subject = DB::table('elective_subjects')->where('code', $request->subject_code)->first();
            }

            return response()->json([
                'success' => true,
                'message' => 'Alias creado exitosamente',
                'alias' => [
                    'id' => $alias->id,
                    'subject_code' => $alias->subject_code,
                    'alias_code' => $alias->alias_code,
                    'description' => $alias->description,
                    'subject_name' => $subject->name ?? 'Desconocida',
                    'subject_credits' => $subject->credits ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating subject alias: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el alias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an alias
     */
    public function destroy(SubjectAlias $alias)
    {
        try {
            $alias->delete();

            return response()->json([
                'success' => true,
                'message' => 'Alias eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting subject alias: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el alias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get aliases for a specific subject
     */
    public function getAliases(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|string'
        ]);

        $aliases = SubjectAlias::where('subject_code', $request->subject_code)->get();

        return response()->json([
            'success' => true,
            'aliases' => $aliases
        ]);
    }

    /**
     * Bulk import aliases from CSV
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120' // 5MB max
        ]);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new \Exception("No se pudo abrir el archivo");
            }

            // Skip header
            fgetcsv($handle);
            
            $imported = 0;
            $errors = [];
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 3) continue; // Need at least: subject_code, alias_code, type
                
                [$subjectCode, $aliasCode, $type, $notes] = array_pad($row, 4, null);
                
                // Validate
                if (empty($subjectCode) || empty($aliasCode) || empty($type)) {
                    $errors[] = "Fila incompleta: " . implode(',', $row);
                    continue;
                }
                
                // Check if already exists
                if (SubjectAlias::where('alias_code', $aliasCode)->exists()) {
                    $errors[] = "Alias '{$aliasCode}' ya existe";
                    continue;
                }
                
                SubjectAlias::create([
                    'subject_code' => trim($subjectCode),
                    'alias_code' => trim($aliasCode),
                    'subject_type' => trim($type),
                    'notes' => $notes ? trim($notes) : null
                ]);
                
                $imported++;
            }
            
            fclose($handle);
            
            return response()->json([
                'success' => true,
                'message' => "Importación completada: {$imported} alias importados",
                'imported' => $imported,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk importing aliases: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al importar: ' . $e->getMessage()
            ], 500);
        }
    }
}
