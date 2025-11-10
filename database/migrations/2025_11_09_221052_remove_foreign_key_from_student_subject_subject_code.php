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
        Schema::table('student_subject', function (Blueprint $table) {
            // Drop the foreign key constraint that only allows subjects table codes
            $table->dropForeign(['subject_code']);
            
            // Keep the column but remove the foreign key constraint
            // This allows both subjects and elective_subjects codes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_subject', function (Blueprint $table) {
            // Restore the foreign key constraint
            $table->foreign('subject_code')
                  ->references('code')
                  ->on('subjects')
                  ->onDelete('cascade');
        });
    }
};
