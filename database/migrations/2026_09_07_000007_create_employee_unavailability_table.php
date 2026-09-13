<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_unavailability', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->unsignedBigInteger('employee_id');
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->string('reason', 30);
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index(['employee_id', 'start_at'], 'employee_unavailability_employee_start_idx');
        });

        DB::statement("ALTER TABLE employee_unavailability ADD CONSTRAINT employee_unavailability_reason_valid CHECK (reason IN ('break', 'leave', 'manual_block'))");
        DB::statement('ALTER TABLE employee_unavailability ADD CONSTRAINT employee_unavailability_time_range CHECK (start_at < end_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_unavailability');
    }
};
