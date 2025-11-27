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
        Schema::table('convalidation_groups', function (Blueprint $table) {
            $table->enum('component_type', [
                'fundamental_required',
                'professional_required',
                'optional_fundamental',
                'optional_professional',
                'free_elective',
                'thesis',
                'leveling'
            ])->nullable()->after('equivalence_percentage')
              ->comment('Curricular component this group belongs to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('convalidation_groups', function (Blueprint $table) {
            $table->dropColumn('component_type');
        });
    }
};
