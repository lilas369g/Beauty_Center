<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_type', 20)->default('user');
            $table->string('action', 100);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->uuid('entity_uuid')->nullable();
            $table->string('result', 20)->default('success');
            $table->string('reason', 255)->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->uuid('request_id')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('actor_user_id', 'audit_logs_actor_user_id_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_actor_type_valid CHECK (actor_type IN ('user', 'system'))");
        DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_result_valid CHECK (result IN ('success', 'denied', 'failed'))");
        DB::statement('CREATE INDEX audit_logs_entity_created_idx ON audit_logs (entity_type, entity_id, created_at DESC)');
        DB::statement('CREATE INDEX audit_logs_actor_created_idx ON audit_logs (actor_user_id, created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
