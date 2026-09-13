<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 100);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->unique('code', 'permissions_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
