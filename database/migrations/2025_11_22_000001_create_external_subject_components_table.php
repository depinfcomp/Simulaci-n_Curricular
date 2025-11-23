<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table stores the manually assigned academic component for each external subject.
     * Required for calculating student progress correctly when simulating transfers.
     */
    public function up(): void
    {
        Schema::create('external_subject_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_subject_id')->constrained('external_subjects')->onDelete('cascade');
            $table->enum('component_type', [
                'fundamental_required',
                'professional_required', 
                'optional_fundamental',
                'optional_professional',
                'free_elective',
                'thesis',
                'leveling'
            ])->comment('Academic component type');
            $table->string('assigned_by')->nullable()->comment('User who assigned the component');
            $table->text('notes')->nullable()->comment('Additional notes about the assignment');
            $table->timestamps();
            
            // One component assignment per external subject
            $table->unique('external_subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_subject_components');
    }
};
