<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_service_areas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('laser_service_setting_id');
            $table->unsignedBigInteger('laser_area_id');
            $table->decimal('default_price', 12, 2)->nullable();
            $table->smallInteger('default_duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['laser_service_setting_id', 'laser_area_id'], 'laser_service_areas_setting_area_unique');
            $table->foreign('laser_service_setting_id', 'laser_service_areas_setting_id_foreign')
                ->references('id')->on('laser_service_settings')->restrictOnDelete();
            $table->foreign('laser_area_id', 'laser_service_areas_area_id_foreign')
                ->references('id')->on('laser_areas')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE laser_service_areas ADD CONSTRAINT laser_service_areas_default_price_non_negative CHECK (default_price IS NULL OR default_price >= 0)');
        DB::statement('ALTER TABLE laser_service_areas ADD CONSTRAINT laser_service_areas_default_duration_positive CHECK (default_duration_minutes IS NULL OR default_duration_minutes > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_service_areas');
    }
};
