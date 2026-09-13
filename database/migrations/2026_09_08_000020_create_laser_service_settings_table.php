<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_service_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_id');
            $table->timestampsTz();

            $table->unique('service_id', 'laser_service_settings_service_id_unique');
            $table->foreign('service_id', 'laser_service_settings_service_id_foreign')
                ->references('id')->on('services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_service_settings');
    }
};
