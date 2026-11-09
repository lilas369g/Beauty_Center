<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->integer('display_order')->default(1)->after('description');
        });

        DB::statement('ALTER TABLE service_categories ADD CONSTRAINT service_categories_display_order_positive CHECK (display_order > 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE service_categories DROP CONSTRAINT IF EXISTS service_categories_display_order_positive');

        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn(['description', 'display_order']);
        });
    }
};
