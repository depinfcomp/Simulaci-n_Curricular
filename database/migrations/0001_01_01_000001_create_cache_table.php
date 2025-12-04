<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates two tables for Laravel's cache system: cache (stores cached values) and cache_locks
     * (implements distributed locking mechanism). These tables are used when the cache driver is
     * configured to use the database.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary()->comment('Cache key identifier - primary key for looking up cached values');
            $table->mediumText('value')->comment('Serialized cached value - can store any PHP data type');
            $table->integer('expiration')->comment('Unix timestamp when this cache entry expires and should be removed');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary()->comment('Lock key identifier - primary key for distributed lock management');
            $table->string('owner')->comment('Lock owner identifier - typically a unique token to identify who holds the lock');
            $table->integer('expiration')->comment('Unix timestamp when this lock expires and can be acquired by others');
        });
    }

    /**
     * Drops both cache tables: cache_locks and cache.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
