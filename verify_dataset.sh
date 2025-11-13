#!/usr/bin/env fish

# Script para verificar y validar el dataset después de la importación
# Autor: Sistema de Importación de Historias Académicas
# Fecha: 2025-11-09

echo "🔍 VERIFICACIÓN DEL DATASET DE HISTORIAS ACADÉMICAS"
echo "=================================================="
echo ""

# Conectar a Docker
echo "📦 Conectando al contenedor de la aplicación..."
docker-compose exec app php artisan tinker --execute="

echo '✅ VERIFICACIÓN DE DATOS IMPORTADOS' . PHP_EOL;
echo '===================================' . PHP_EOL . PHP_EOL;

// 1. Verificar total de estudiantes
\$totalStudents = DB::table('students')->count();
echo '📊 Total de estudiantes: ' . \$totalStudents . PHP_EOL;

// 2. Verificar total de registros históricos
\$totalHistorical = DB::table('student_subject')->count();
echo '📚 Total de registros históricos (student_subject): ' . \$totalHistorical . PHP_EOL;

// 3. Verificar total de inscripciones actuales
\$totalCurrent = DB::table('student_current_subjects')->count();
echo '📝 Total de inscripciones actuales: ' . \$totalCurrent . PHP_EOL . PHP_EOL;

// 4. Verificar distribución de créditos
echo '💳 DISTRIBUCIÓN DE CRÉDITOS' . PHP_EOL;
echo '===========================' . PHP_EOL;

\$creditDistribution = DB::table('student_subject')
    ->select('assigned_component', DB::raw('COUNT(*) as count'), DB::raw('SUM(credits_counted) as total_credits'))
    ->whereNotNull('assigned_component')
    ->groupBy('assigned_component')
    ->get();

foreach (\$creditDistribution as \$dist) {
    echo sprintf('  %s: %d materias, %d créditos' . PHP_EOL, 
        \$dist->assigned_component, 
        \$dist->count, 
        \$dist->total_credits ?? 0
    );
}
echo PHP_EOL;

// 5. Verificar materias que NO cuentan para el grado
\$notCounting = DB::table('student_subject')
    ->where('counts_towards_degree', false)
    ->count();
echo '⚠️  Materias que NO cuentan para el grado: ' . \$notCounting . PHP_EOL . PHP_EOL;

// 6. Verificar estados de materias
echo '📈 ESTADOS DE MATERIAS' . PHP_EOL;
echo '=====================' . PHP_EOL;

\$statusDistribution = DB::table('student_subject')
    ->select('status', DB::raw('COUNT(*) as count'))
    ->groupBy('status')
    ->get();

foreach (\$statusDistribution as \$status) {
    echo sprintf('  %s: %d' . PHP_EOL, \$status->status, \$status->count);
}
echo PHP_EOL;

// 7. Verificar promedios y progreso
echo '🎓 MÉTRICAS ACADÉMICAS' . PHP_EOL;
echo '=====================' . PHP_EOL;

\$avgMetrics = DB::table('students')
    ->select(
        DB::raw('AVG(average_grade) as avg_grade'),
        DB::raw('AVG(progress_percentage) as avg_progress'),
        DB::raw('AVG(approved_credits) as avg_credits')
    )
    ->first();

echo sprintf('  Promedio general: %.2f' . PHP_EOL, \$avgMetrics->avg_grade ?? 0);
echo sprintf('  Progreso promedio: %.2f%%' . PHP_EOL, \$avgMetrics->avg_progress ?? 0);
echo sprintf('  Créditos aprobados promedio: %.2f' . PHP_EOL, \$avgMetrics->avg_credits ?? 0);
echo PHP_EOL;

// 8. Verificar top 5 estudiantes por créditos
echo '🏆 TOP 5 ESTUDIANTES POR CRÉDITOS APROBADOS' . PHP_EOL;
echo '===========================================' . PHP_EOL;

\$topStudents = DB::table('students')
    ->orderBy('approved_credits', 'desc')
    ->limit(5)
    ->get(['document', 'name', 'approved_credits', 'average_grade', 'progress_percentage']);

foreach (\$topStudents as \$student) {
    echo sprintf('  %s - %s: %d créditos (%.2f promedio, %.2f%% progreso)' . PHP_EOL,
        \$student->document,
        \$student->name,
        \$student->approved_credits,
        \$student->average_grade,
        \$student->progress_percentage
    );
}
echo PHP_EOL;

// 9. Verificar materias más cursadas
echo '📖 TOP 10 MATERIAS MÁS CURSADAS' . PHP_EOL;
echo '================================' . PHP_EOL;

\$topSubjects = DB::table('student_subject')
    ->select('subject_code', DB::raw('COUNT(*) as students_count'))
    ->groupBy('subject_code')
    ->orderBy('students_count', 'desc')
    ->limit(10)
    ->get();

\$subjectCodes = \$topSubjects->pluck('subject_code')->toArray();
\$subjectNames = DB::table('subjects')
    ->whereIn('code', \$subjectCodes)
    ->pluck('name', 'code');
    
\$electiveNames = DB::table('elective_subjects')
    ->whereIn('code', \$subjectCodes)
    ->pluck('name', 'code');
    
\$allNames = \$subjectNames->merge(\$electiveNames);

foreach (\$topSubjects as \$subject) {
    \$name = \$allNames->get(\$subject->subject_code, 'Desconocida');
    echo sprintf('  %s - %s: %d estudiantes' . PHP_EOL,
        \$subject->subject_code,
        \$name,
        \$subject->students_count
    );
}
echo PHP_EOL;

// 10. Verificar última importación
echo '📥 ÚLTIMA IMPORTACIÓN' . PHP_EOL;
echo '====================' . PHP_EOL;

\$lastImport = DB::table('academic_history_imports')
    ->orderBy('created_at', 'desc')
    ->first();

if (\$lastImport) {
    echo sprintf('  Archivo: %s' . PHP_EOL, \$lastImport->original_filename);
    echo sprintf('  Total registros: %d' . PHP_EOL, \$lastImport->total_records);
    echo sprintf('  Exitosos: %d (%.2f%%)' . PHP_EOL, 
        \$lastImport->successful_imports,
        (\$lastImport->total_records > 0 ? (\$lastImport->successful_imports / \$lastImport->total_records * 100) : 0)
    );
    echo sprintf('  Fallidos: %d (%.2f%%)' . PHP_EOL, 
        \$lastImport->failed_imports,
        (\$lastImport->total_records > 0 ? (\$lastImport->failed_imports / \$lastImport->total_records * 100) : 0)
    );
    echo sprintf('  Estado: %s' . PHP_EOL, \$lastImport->status);
    echo sprintf('  Fecha: %s' . PHP_EOL, \$lastImport->created_at);
}
echo PHP_EOL;

echo '✅ VERIFICACIÓN COMPLETADA' . PHP_EOL;
"

echo ""
echo "✅ Verificación completada"
