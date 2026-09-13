<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_service_laser_areas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('appointment_service_id');
            $table->unsignedBigInteger('laser_service_area_id');
            $table->unsignedBigInteger('performed_by_employee_id')->nullable();
            $table->string('status', 20)->default('planned');
            $table->string('area_name_snapshot', 150);
            $table->decimal('price_snapshot', 12, 2)->nullable();
            $table->smallInteger('duration_snapshot_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['appointment_service_id', 'laser_service_area_id'], 'appointment_service_laser_areas_appointment_service_area_unique');
            $table->index('performed_by_employee_id', 'appointment_service_laser_areas_performed_by_employee_idx');
            $table->foreign('appointment_service_id', 'appointment_service_laser_areas_appointment_service_id_foreign')
                ->references('id')->on('appointment_services')->cascadeOnDelete();
            $table->foreign('laser_service_area_id', 'appointment_service_laser_areas_laser_service_area_id_foreign')
                ->references('id')->on('laser_service_areas')->restrictOnDelete();
            $table->foreign('performed_by_employee_id', 'appointment_service_laser_areas_performed_by_employee_id_foreign')
                ->references('id')->on('employees')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE appointment_service_laser_areas ADD CONSTRAINT appointment_service_laser_areas_status_valid CHECK (status IN ('planned', 'completed', 'skipped'))");
        DB::statement('ALTER TABLE appointment_service_laser_areas ADD CONSTRAINT appointment_service_laser_areas_price_non_negative CHECK (price_snapshot IS NULL OR price_snapshot >= 0)');
        DB::statement('ALTER TABLE appointment_service_laser_areas ADD CONSTRAINT appointment_service_laser_areas_duration_positive CHECK (duration_snapshot_minutes IS NULL OR duration_snapshot_minutes > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service_laser_areas');
    }
};
