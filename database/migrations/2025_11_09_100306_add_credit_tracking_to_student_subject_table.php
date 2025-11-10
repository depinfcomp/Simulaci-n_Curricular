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
            $table->boolean('counts_towards_degree')->default(true)->after('status')
                ->comment('Whether credits count toward degree (false for lost credits)');
            $table->string('assigned_component', 50)->nullable()->after('counts_towards_degree')
                ->comment('Component to which credits were assigned (fundamental_required, professional_required, etc.)');
            $table->integer('credits_counted')->default(0)->after('assigned_component')
                ->comment('Actual credits counted (may be partial if subject exceeded component limit)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_subject', function (Blueprint $table) {
            $table->dropColumn(['counts_towards_degree', 'assigned_component', 'credits_counted']);
        });
    }
};
