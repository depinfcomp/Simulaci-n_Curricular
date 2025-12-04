<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the students table which stores basic student information
     * and their calculated academic metrics.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key');
            $table->string('document', 20)->unique()->comment('Unique student identification document number');
            
            // Academic metrics calculated by StudentMetricsService
            $table->integer('total_credits_taken')->default(0)->comment('Total credits attempted across all subjects including failed ones');
            $table->integer('approved_credits')->default(0)->comment('Total approved credits that count toward degree completion');
            $table->decimal('average_grade', 4, 2)->default(0)->comment('Weighted average grade (0.00 to 5.00 scale)');
            $table->decimal('progress_percentage', 5, 2)->default(0)->comment('Academic progress percentage toward degree completion (0.00 to 100.00)');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the students table.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
