<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->unsignedBigInteger('service_category_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->smallInteger('duration_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->foreign('service_category_id')
                ->references('id')->on('service_categories')
                ->restrictOnDelete();
        });

        DB::statement('ALTER TABLE services ADD CONSTRAINT services_price_non_negative CHECK (price >= 0)');
        DB::statement('ALTER TABLE services ADD CONSTRAINT services_duration_positive CHECK (duration_minutes > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
