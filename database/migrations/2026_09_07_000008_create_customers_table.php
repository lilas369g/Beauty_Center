<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->string('full_name', 150);
            $table->string('phone', 30)->unique();
            $table->string('email', 255)->nullable()->unique();
            $table->text('address')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->timestampTz('registered_at')->useCurrent();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('ALTER TABLE customers ADD CONSTRAINT customers_loyalty_points_non_negative CHECK (loyalty_points >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
