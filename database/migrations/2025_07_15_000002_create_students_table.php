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
        Schema::create('students', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementable ID');
            $table->string('name')->comment('Student full name');
            $table->string('document', 20)->nullable()->unique()->after('name')->comment('Student ID document number');
            $table->decimal('progress_percentage', 5, 2)->default(0)->comment('Academic progress percentage (0-100)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
