<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the external_subject_components table which stores manually assigned academic components
     * for external subjects. This is essential for calculating student progress correctly when simulating
     * curriculum transfers, as external subjects need to be mapped to curricular components (fundamental,
     * professional, elective, etc.) to properly track credit distribution.
     */
    public function up(): void
    {
        Schema::create('external_subject_components', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this component assignment');
            $table->foreignId('external_subject_id')->constrained('external_subjects')->onDelete('cascade')->comment('Foreign key to external_subjects table identifying which external subject this component assignment applies to');
            $table->enum('component_type', [
                'fundamental_required',
                'professional_required', 
                'optional_fundamental',
                'optional_professional',
                'free_elective',
                'thesis',
                'leveling'
            ])->comment('Academic component type assigned to this external subject: fundamental_required (basic required courses), professional_required (major required courses), optional_fundamental (optional general education), optional_professional (optional major courses), free_elective (any elective), thesis (capstone project), leveling (remedial courses)');
            $table->string('assigned_by')->nullable()->comment('Username or ID of the administrator who manually assigned this component');
            $table->text('notes')->nullable()->comment('Additional notes explaining the component assignment decision or special conditions');
            $table->timestamps();
            
            // Ensures each external subject has exactly one component assignment
            $table->unique('external_subject_id')->comment('Unique constraint ensuring one component assignment per external subject');
        });
    }

    /**
     * Drops the external_subject_components table.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_subject_components');
    }
};
