<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['role_id', 'permission_id'], 'role_permissions_pkey');
            $table->foreign('role_id', 'role_permissions_role_id_foreign')
                ->references('id')->on('roles')->restrictOnDelete();
            $table->foreign('permission_id', 'role_permissions_permission_id_foreign')
                ->references('id')->on('permissions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
