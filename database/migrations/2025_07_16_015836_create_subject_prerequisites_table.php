<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the subject_prerequisites table which defines prerequisite relationships between subjects.
     * This is a many-to-many self-referential table that tracks which subjects must be completed before
     * a student can enroll in other subjects.
     */
    public function up(): void
    {
        Schema::create('subject_prerequisites', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this prerequisite relationship');
            $table->string('subject_code', 10)->comment('Code of the subject that requires prerequisites (foreign key to subjects.code)');
            $table->string('prerequisite_code', 10)->comment('Code of the required prerequisite subject (foreign key to subjects.code)');
            $table->timestamps();
            
            // Foreign key constraints ensure referential integrity
            $table->foreign('subject_code')->references('code')->on('subjects')->onDelete('cascade');
            $table->foreign('prerequisite_code')->references('code')->on('subjects')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate prerequisite relationships
            $table->unique(['subject_code', 'prerequisite_code']);
        });
    }

    /**
     * Drops the subject_prerequisites table.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_prerequisites');
    }
};
