<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->string('full_name', 150);
            $table->string('email', 255)->nullable()->unique();
            $table->string('phone', 30)->nullable()->unique();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('ALTER TABLE employees ADD CONSTRAINT employees_commission_rate_range CHECK (commission_rate >= 0 AND commission_rate <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
