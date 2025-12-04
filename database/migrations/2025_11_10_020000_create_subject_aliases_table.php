<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the subject_aliases table which maps alternative subject codes to their primary codes.
     * This is useful for handling historical subject codes, merged courses, or subjects that have been
     * renamed over time while maintaining backward compatibility in student records and imports.
     */
    public function up(): void
    {
        // Drop if exists to avoid conflicts during migration
        Schema::dropIfExists('subject_aliases');
        
        Schema::create('subject_aliases', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this alias mapping');
            $table->string('subject_code', 20)->comment('Primary subject code that is currently used in the system (the canonical code)');
            $table->string('alias_code', 20)->comment('Alternative/historical subject code that should map to the primary code (used in old records or by other systems)');
            $table->text('description')->nullable()->comment('Explanation of the alias relationship (e.g., "Legacy code from 2018 curriculum", "Merged with subject X in 2020")');
            $table->timestamps();
            
            // Indexes for fast lookups in both directions
            $table->index('subject_code')->comment('Index for finding all aliases of a primary subject code');
            $table->index('alias_code')->comment('Index for resolving an alias code to its primary subject code');
            
            // Prevent duplicate alias mappings
            $table->unique(['subject_code', 'alias_code'])->comment('Ensures each alias code can only map to one primary subject code');
        });
    }

    /**
     * Drops the subject_aliases table.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_aliases');
    }
};
