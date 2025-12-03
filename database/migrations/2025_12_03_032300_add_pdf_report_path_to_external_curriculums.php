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
        Schema::table('external_curriculums', function (Blueprint $table) {
            $table->string('pdf_report_path')->nullable()->after('metadata')
                ->comment('Path to the PDF report generated when saving curriculum version from simulation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_curriculums', function (Blueprint $table) {
            $table->dropColumn('pdf_report_path');
        });
    }
};
