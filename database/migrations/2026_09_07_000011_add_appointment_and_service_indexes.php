<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX services_active_category_name_idx ON services (service_category_id, name) WHERE is_active = true');
        DB::statement("CREATE INDEX appointments_employee_active_start_idx ON appointments (employee_id, start_at) WHERE status IN ('pending', 'confirmed')");
        DB::statement("CREATE INDEX appointments_active_start_idx ON appointments (start_at) WHERE status IN ('pending', 'confirmed')");
        DB::statement('CREATE INDEX appointments_customer_start_idx ON appointments (customer_id, start_at DESC)');
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_no_active_employee_overlap EXCLUDE USING gist (employee_id WITH =, tstzrange(start_at, end_at, '[)') WITH &&) WHERE (status IN ('pending', 'confirmed'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_no_active_employee_overlap');
        DB::statement('DROP INDEX IF EXISTS appointments_customer_start_idx');
        DB::statement('DROP INDEX IF EXISTS appointments_active_start_idx');
        DB::statement('DROP INDEX IF EXISTS appointments_employee_active_start_idx');
        DB::statement('DROP INDEX IF EXISTS services_active_category_name_idx');
    }
};
