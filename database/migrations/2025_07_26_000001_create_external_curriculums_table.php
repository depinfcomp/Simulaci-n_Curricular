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
            $table->id()->comment('Auto-incrementable ID');
            $table->string('name')->comment('External curriculum name');
            $table->string('institution')->nullable()->comment('Source institution name');
            $table->text('description')->nullable()->comment('Curriculum description');
            $table->string('uploaded_file')->nullable()->comment('Path to original Excel file');
            $table->json('metadata')->nullable()->comment('Additional metadata');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('Curriculum status');
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
