<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('name', 150);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();

            $table->foreign('role_id', 'users_role_id_foreign')
                ->references('id')->on('roles')->restrictOnDelete();
            $table->foreign('employee_id', 'users_employee_id_foreign')
                ->references('id')->on('employees')->nullOnDelete();
            $table->unique('employee_id', 'users_employee_id_unique');
            $table->unique('email', 'users_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
