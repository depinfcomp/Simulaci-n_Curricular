<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the credit_limits_config table which defines maximum credit limits for each curricular
     * component. These limits control credit distribution and overflow behavior when students exceed
     * component limits. Can be configured globally or per external curriculum.
     */
    public function up(): void
    {
        Schema::create('credit_limits_config', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this credit limit configuration');
            $table->foreignId('external_curriculum_id')
                  ->nullable()
                  ->constrained('external_curriculums')
                  ->onDelete('cascade')
                  ->comment('Foreign key to external_curriculums table (null indicates global default configuration that applies to all curricula without specific settings)');
            
            // Credit limits for each curricular component (all values are required and must be positive integers)
            $table->integer('max_free_elective_credits')
                  ->comment('Maximum number of free elective credits allowed (excess credits beyond all component limits are lost)');
            
            $table->integer('max_optional_professional_credits')
                  ->comment('Maximum number of optional professional/disciplinary credits allowed (overflow redirects to free elective if available)');
            
            $table->integer('max_required_fundamental_credits')
                  ->comment('Maximum number of required fundamental credits allowed (typically matches curriculum requirements exactly)');
            
            $table->integer('max_optional_fundamental_credits')
                  ->comment('Maximum number of optional fundamental credits allowed (overflow redirects to free elective if available)');
            
            $table->integer('max_required_professional_credits')
                  ->comment('Maximum number of required professional/disciplinary credits allowed (typically matches curriculum requirements exactly)');
            
            $table->integer('max_leveling_credits')
                  ->comment('Maximum number of leveling credits allowed (remedial courses taken before or alongside regular curriculum)');
            
            $table->integer('max_thesis_credits')
                  ->comment('Maximum number of thesis/capstone project credits allowed (typically fixed by curriculum at 18-20 credits)');
            
            $table->timestamps();
            
            // Unique constraint ensures only one configuration per curriculum (or one global default)
            $table->unique('external_curriculum_id')->comment('Ensures each external curriculum has at most one credit limit configuration');
        });
    }

    /**
     * Drops the credit_limits_config table.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_limits_config');
    }
};
