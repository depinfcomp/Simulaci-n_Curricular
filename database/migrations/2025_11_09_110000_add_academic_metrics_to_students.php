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
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('average_grade', 4, 2)->default(0)->after('progress_percentage')
                  ->comment('Weighted average grade: Σ(Credits × Grade) / Σ(Credits)');
            $table->integer('total_credits_taken')->default(0)->after('average_grade')
                  ->comment('Total credits taken (including failed and current subjects)');
            $table->integer('approved_credits')->default(0)->after('total_credits_taken')
                  ->comment('Total approved credits (passing grade)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['average_grade', 'total_credits_taken', 'approved_credits']);
        });
    }
};
