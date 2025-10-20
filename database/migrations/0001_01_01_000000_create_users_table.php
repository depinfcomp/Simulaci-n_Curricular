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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementable ID');
            $table->string('name')->comment('User full name');
            $table->string('email')->unique()->comment('Unique email address');
            $table->timestamp('email_verified_at')->nullable()->comment('Email verification timestamp');
            $table->string('password')->comment('Encrypted password');
            $table->rememberToken()->comment('Remember me token');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('User email - Primary key');
            $table->string('token')->comment('Password reset token');
            $table->timestamp('created_at')->nullable()->comment('Token creation timestamp');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Session ID - Primary key');
            $table->foreignId('user_id')->nullable()->index()->comment('Foreign key to users');
            $table->string('ip_address', 45)->nullable()->comment('Client IP address');
            $table->text('user_agent')->nullable()->comment('Client user agent string');
            $table->longText('payload')->comment('Session payload data');
            $table->integer('last_activity')->index()->comment('Last activity timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
