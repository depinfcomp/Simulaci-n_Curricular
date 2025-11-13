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
        Schema::table('academic_histories', function (Blueprint $table) {
            $table->boolean('counts_towards_degree')->default(true)->after('status')
                  ->comment('Whether these credits count towards degree progress (false for excess credits)');
            $table->string('assigned_component')->nullable()->after('counts_towards_degree')
                  ->comment('Component where credits were assigned: fundamental_required, professional_required, optional_fundamental, optional_professional, free_elective, lost');
            $table->integer('credits_counted')->default(0)->after('assigned_component')
                  ->comment('Credits actually counted (may be less than total credits if partial)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_histories', function (Blueprint $table) {
            $table->dropColumn(['counts_towards_degree', 'assigned_component', 'credits_counted']);
        });
    }
};
