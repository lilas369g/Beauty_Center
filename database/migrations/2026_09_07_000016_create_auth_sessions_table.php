<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->char('token_hash', 64);
            $table->string('device_name', 150)->nullable();
            $table->timestampTz('expires_at');
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestampsTz();

            $table->foreign('user_id', 'auth_sessions_user_id_foreign')
                ->references('id')->on('users')->restrictOnDelete();
            $table->unique('token_hash', 'auth_sessions_token_hash_unique');
            $table->index(['user_id', 'revoked_at', 'expires_at'], 'auth_sessions_user_revoked_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_sessions');
    }
};
