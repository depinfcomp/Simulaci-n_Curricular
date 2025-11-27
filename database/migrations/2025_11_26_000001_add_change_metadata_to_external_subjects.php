<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields to track the state of subjects imported from simulation:
     * - change_type: added, removed, modified, moved, unchanged
     * - original_semester: original semester before changes
     * - change_details: JSON with detailed information about the change
     */
    public function up(): void
    {
        Schema::table('external_subjects', function (Blueprint $table) {
            $table->enum('change_type', ['added', 'removed', 'modified', 'moved', 'unchanged'])
                  ->nullable()
                  ->after('additional_data')
                  ->comment('Type of change made in simulation');
            
            $table->integer('original_semester')
                  ->nullable()
                  ->after('change_type')
                  ->comment('Original semester before modifications');
            
            $table->json('change_details')
                  ->nullable()
                  ->after('original_semester')
                  ->comment('Detailed information about the change (prerequisites, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_subjects', function (Blueprint $table) {
            $table->dropColumn(['change_type', 'original_semester', 'change_details']);
        });
    }
};
