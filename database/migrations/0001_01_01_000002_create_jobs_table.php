<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates three tables for Laravel's queue system: jobs (pending queue jobs), job_batches
     * (groups of jobs processed together), and failed_jobs (jobs that failed execution). These
     * tables manage asynchronous task processing when the queue driver is set to database.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this queued job');
            $table->string('queue')->index()->comment('Queue name where this job belongs (e.g., "default", "emails", "imports") - indexed for quick queue filtering');
            $table->longText('payload')->comment('Serialized job data including class name, method, parameters, and metadata');
            $table->unsignedTinyInteger('attempts')->comment('Number of times this job has been attempted (used for retry logic)');
            $table->unsignedInteger('reserved_at')->nullable()->comment('Unix timestamp when this job was reserved by a worker (null if not currently processing)');
            $table->unsignedInteger('available_at')->comment('Unix timestamp when this job becomes available for processing (used for delayed jobs)');
            $table->unsignedInteger('created_at')->comment('Unix timestamp when this job was created and queued');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Unique batch identifier - primary key');
            $table->string('name')->comment('Human-readable batch name for identification');
            $table->integer('total_jobs')->comment('Total number of jobs in this batch');
            $table->integer('pending_jobs')->comment('Number of jobs still pending execution');
            $table->integer('failed_jobs')->comment('Number of jobs that failed in this batch');
            $table->longText('failed_job_ids')->comment('Serialized array of failed job IDs for tracking');
            $table->mediumText('options')->nullable()->comment('Serialized batch configuration options');
            $table->integer('cancelled_at')->nullable()->comment('Unix timestamp when this batch was cancelled (null if not cancelled)');
            $table->integer('created_at')->comment('Unix timestamp when this batch was created');
            $table->integer('finished_at')->nullable()->comment('Unix timestamp when all jobs in this batch completed (null if still running)');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this failed job record');
            $table->string('uuid')->unique()->comment('UUID for this failed job - unique identifier for external reference');
            $table->text('connection')->comment('Queue connection name where the job failed');
            $table->text('queue')->comment('Queue name where the job failed');
            $table->longText('payload')->comment('Original serialized job data including class, method, and parameters');
            $table->longText('exception')->comment('Full exception stack trace showing why the job failed');
            $table->timestamp('failed_at')->useCurrent()->comment('Timestamp when the job failed');
        });
    }

    /**
     * Drops all queue-related tables: failed_jobs, job_batches, and jobs.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
