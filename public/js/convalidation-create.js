// Convalidation Create Page Scripts

function downloadTemplate() {
    // Create a CSV template
    const csvContent = "codigo,nombre,creditos,semestre,descripcion\n" +
                      "INF101,Introducción a la Informática,3,1,Conceptos básicos de informática\n" +
                      "MAT101,Matemáticas I,4,1,Álgebra y cálculo básico\n" +
                      "PRG101,Programación I,4,2,Fundamentos de programación\n" +
                      "PRG102,Programación II,4,3,Programación avanzada\n" +
                      "BD101,Bases de Datos I,3,4,Fundamentos de bases de datos";
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'plantilla_malla_curricular.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// File validation
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('excel_file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
                
                if (file.size > maxSize) {
                    alert('El archivo es demasiado grande. El tamaño máximo es 10MB.');
                    e.target.value = '';
                    return;
                }
                
                if (!allowedTypes.includes(file.type) && !file.name.endsWith('.csv')) {
                    alert('Tipo de archivo no válido. Solo se permiten archivos CSV.');
                    e.target.value = '';
                    return;
                }
            }
        });
    }
});
