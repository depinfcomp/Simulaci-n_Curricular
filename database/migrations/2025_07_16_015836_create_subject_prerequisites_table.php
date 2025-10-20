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
        Schema::create('subject_prerequisites', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementable ID');
            $table->string('subject_code', 10)->comment('Subject that requires prerequisites');
            $table->string('prerequisite_code', 10)->comment('Required prerequisite subject code');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('subject_code')->references('code')->on('subjects')->onDelete('cascade');
            $table->foreign('prerequisite_code')->references('code')->on('subjects')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate prerequisites
            $table->unique(['subject_code', 'prerequisite_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_prerequisites');
    }
};
