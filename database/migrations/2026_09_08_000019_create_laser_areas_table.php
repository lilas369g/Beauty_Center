<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_areas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->string('code', 100)->unique();
            $table->string('name', 150);
            $table->smallInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE laser_areas ADD CONSTRAINT laser_areas_sort_order_positive CHECK (sort_order > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_areas');
    }
};
