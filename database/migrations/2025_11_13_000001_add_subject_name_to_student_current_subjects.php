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
        Schema::table('student_current_subjects', function (Blueprint $table) {
            $table->string('subject_name', 255)->nullable()->after('subject_code')->comment('Subject name from import');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_current_subjects', function (Blueprint $table) {
            $table->dropColumn('subject_name');
        });
    }
};
