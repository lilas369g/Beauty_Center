<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_status_valid CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled', 'no_show'))");
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_time_range CHECK (start_at < end_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
