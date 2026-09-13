<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_services', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('service_id');
            $table->decimal('booked_price', 12, 2);
            $table->smallInteger('booked_duration_minutes');
            $table->smallInteger('position')->default(1);
            $table->timestampsTz();

            $table->primary(['appointment_id', 'service_id']);
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE appointment_services ADD CONSTRAINT appointment_services_price_non_negative CHECK (booked_price >= 0)');
        DB::statement('ALTER TABLE appointment_services ADD CONSTRAINT appointment_services_duration_positive CHECK (booked_duration_minutes > 0)');
        DB::statement('ALTER TABLE appointment_services ADD CONSTRAINT appointment_services_position_positive CHECK (position > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_services');
    }
};
