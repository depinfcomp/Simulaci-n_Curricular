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
        if (!Schema::hasColumn('subjects', 'is_leveling')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_leveling')->default(false)->after('is_required');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('subjects', 'is_leveling')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('is_leveling');
            });
        }
    }
};
