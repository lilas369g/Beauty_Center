<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_working_hours', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->unsignedBigInteger('employee_id');
            $table->smallInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestampsTz();

            $table->unique(['employee_id', 'weekday', 'start_time', 'end_time'], 'employee_working_hours_exact_unique');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index(['employee_id', 'weekday', 'start_time'], 'employee_working_hours_employee_weekday_start_idx');
        });

        DB::statement('ALTER TABLE employee_working_hours ADD CONSTRAINT employee_working_hours_weekday_range CHECK (weekday BETWEEN 0 AND 6)');
        DB::statement('ALTER TABLE employee_working_hours ADD CONSTRAINT employee_working_hours_time_range CHECK (start_time < end_time)');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_working_hours');
    }
};
