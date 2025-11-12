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
            $table->string('document', 20)->unique()->comment('Student ID document number');
            
            // Academic metrics (calculated by StudentMetricsService)
            $table->integer('total_credits_taken')->default(0)->comment('Total credits attempted (all subjects)');
            $table->integer('approved_credits')->default(0)->comment('Credits that count toward degree');
            $table->decimal('average_grade', 4, 2)->default(0)->comment('Weighted average grade');
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
