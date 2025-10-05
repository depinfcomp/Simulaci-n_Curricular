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
        Schema::create('external_curriculums', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // External curriculum name (e.g., "XYZ University - Systems Engineering")
            $table->string('institution')->nullable(); // Source institution
            $table->text('description')->nullable(); // Curriculum description
            $table->string('uploaded_file')->nullable(); // Path to original Excel file
            $table->json('metadata')->nullable(); // Additional metadata
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_curriculums');
    }
};
