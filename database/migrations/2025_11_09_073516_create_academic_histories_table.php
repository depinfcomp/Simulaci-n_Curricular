<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_history_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_filename');
            $table->integer('total_records')->default(0);
            $table->integer('successful_imports')->default(0);
            $table->integer('failed_imports')->default(0);
            $table->json('column_mapping')->nullable(); // Store manual column mappings
            $table->json('import_summary')->nullable(); // Store import statistics
            $table->text('error_log')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->foreignId('imported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('academic_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('academic_history_imports')->onDelete('cascade');
            $table->string('student_code')->index();
            $table->string('subject_code');
            $table->string('subject_name');
            $table->string('grade')->nullable();
            $table->decimal('numeric_grade', 3, 1)->nullable();
            $table->integer('credits')->default(0);
            $table->string('period')->nullable(); // 2024-1, 2024-2, etc.
            $table->enum('status', ['approved', 'failed', 'in_progress', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['student_code', 'subject_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_histories');
        Schema::dropIfExists('academic_history_imports');
    }
};
