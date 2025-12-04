<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates three authentication-related tables: users (application users), password_reset_tokens
     * (for password recovery), and sessions (for storing user session data). These are Laravel's
     * default authentication tables required for user management and session handling.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this user');
            $table->string('name')->comment('User full name for display purposes');
            $table->string('email')->unique()->comment('Unique email address used for login and communications');
            $table->timestamp('email_verified_at')->nullable()->comment('Timestamp when user verified their email address (null if not verified)');
            $table->string('password')->comment('Hashed password using bcrypt or argon2');
            $table->boolean('must_change_password')->default(false)->comment('Flag indicating if user must change password on next login (for security or first-time setup)');
            $table->rememberToken()->comment('Token for "remember me" persistent login functionality');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('User email address - serves as primary key for password reset requests');
            $table->string('token')->comment('Hashed password reset token sent to user email');
            $table->timestamp('created_at')->nullable()->comment('Token creation timestamp - used to expire old tokens');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Session identifier - primary key');
            $table->foreignId('user_id')->nullable()->index()->comment('Foreign key to users table if session is authenticated (null for guest sessions)');
            $table->string('ip_address', 45)->nullable()->comment('Client IP address (supports both IPv4 and IPv6)');
            $table->text('user_agent')->nullable()->comment('Client browser user agent string for device identification');
            $table->longText('payload')->comment('Serialized session data payload containing session variables');
            $table->integer('last_activity')->index()->comment('Unix timestamp of last activity - used for session expiration');
        });
    }

    /**
     * Drops the authentication tables: sessions, password_reset_tokens, and users (in reverse order).
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
