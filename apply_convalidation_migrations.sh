#!/bin/bash

# Script para aplicar las nuevas migraciones del sistema de convalidaciones
# Fecha: 2025-11-22

echo "================================================"
echo "Sistema de Simulación de Convalidaciones"
echo "Aplicando migraciones de base de datos"
echo "================================================"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo "❌ Error: No se encuentra el archivo artisan."
    echo "   Asegúrate de ejecutar este script desde el directorio raíz del proyecto."
    exit 1
fi

echo "📋 Migraciones que se aplicarán:"
echo "   1. external_subject_components - Asignación de componentes académicos"
echo "   2. convalidation_simulations - Sesiones de simulación"
echo "   3. convalidation_equivalence_rules - Reglas de equivalencia N:N"
echo "   4. simulation_student_results - Resultados por estudiante"
echo ""

read -p "¿Deseas continuar? (s/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "❌ Operación cancelada"
    exit 0
fi

echo ""
echo "🚀 Ejecutando migraciones..."
echo ""

php artisan migrate --path=database/migrations/2025_11_22_000001_create_external_subject_components_table.php
if [ $? -ne 0 ]; then
    echo "❌ Error al crear tabla external_subject_components"
    exit 1
fi

php artisan migrate --path=database/migrations/2025_11_22_000002_create_convalidation_simulations_table.php
if [ $? -ne 0 ]; then
    echo "❌ Error al crear tabla convalidation_simulations"
    exit 1
fi

php artisan migrate --path=database/migrations/2025_11_22_000003_create_convalidation_equivalence_rules_table.php
if [ $? -ne 0 ]; then
    echo "❌ Error al crear tabla convalidation_equivalence_rules"
    exit 1
fi

php artisan migrate --path=database/migrations/2025_11_22_000004_create_simulation_student_results_table.php
if [ $? -ne 0 ]; then
    echo "❌ Error al crear tabla simulation_student_results"
    exit 1
fi

echo ""
echo "================================================"
echo "✅ Migraciones aplicadas exitosamente"
echo "================================================"
echo ""
echo "📊 Resumen de tablas creadas:"
echo "   ✓ external_subject_components"
echo "   ✓ convalidation_simulations"
echo "   ✓ convalidation_equivalence_rules"
echo "   ✓ simulation_student_results"
echo ""
echo "📚 Modelos Eloquent disponibles:"
echo "   ✓ ExternalSubjectComponent"
echo "   ✓ ConvalidationSimulation"
echo "   ✓ ConvalidationEquivalenceRule"
echo "   ✓ SimulationStudentResult"
echo ""
echo "🎯 Próximos pasos:"
echo "   1. Implementar endpoints API en ConvalidationController"
echo "   2. Crear vistas frontend para asignación de componentes"
echo "   3. Probar con datos de ejemplo"
echo ""
echo "📖 Ver documentación completa en:"
echo "   documentation/CONVALIDATION_IMPLEMENTATION_SUMMARY.md"
echo "   documentation/CONVALIDATION_SIMULATION_SYSTEM.md"
echo "   documentation/CONVALIDATION_QUICKSTART_GUIDE.md"
echo ""
