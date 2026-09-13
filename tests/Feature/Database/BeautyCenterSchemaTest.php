<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BeautyCenterSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('pgsql', DB::connection()->getDriverName(), 'Schema tests must run against PostgreSQL.');
    }

    public function test_migrations_create_the_v1_schema(): void
    {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename IN ('service_categories', 'services', 'employees', 'employee_services', 'employee_working_hours', 'employee_unavailability', 'customers', 'appointments', 'appointment_services')");

        $this->assertCount(9, $tables);
        $this->assertSame('pgcrypto', DB::scalar("SELECT extname FROM pg_extension WHERE extname = 'pgcrypto'"));
        $this->assertSame('btree_gist', DB::scalar("SELECT extname FROM pg_extension WHERE extname = 'btree_gist'"));
    }

    public function test_unique_identifiers_categories_customer_phone_and_non_null_emails_are_enforced(): void
    {
        $uuid = (string) Str::uuid();
        DB::table('service_categories')->insert(['uuid' => $uuid, 'name' => 'Hair', 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(QueryException::class);
        DB::table('service_categories')->insert(['uuid' => $uuid, 'name' => 'Nails', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_category_name_employee_contact_and_customer_contact_uniqueness_are_enforced(): void
    {
        DB::table('service_categories')->insert(['name' => 'Hair', 'created_at' => now(), 'updated_at' => now()]);
        $this->expectException(QueryException::class);
        DB::table('service_categories')->insert(['name' => 'Hair', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_unique_employee_and_customer_contacts_are_enforced(): void
    {
        DB::table('employees')->insert(['full_name' => 'Aya', 'email' => 'aya@example.test', 'phone' => '700000001', 'created_at' => now(), 'updated_at' => now()]);
        $this->expectException(QueryException::class);
        DB::table('employees')->insert(['full_name' => 'Maya', 'email' => 'aya@example.test', 'phone' => '700000002', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_customer_phone_and_email_are_unique_when_present(): void
    {
        DB::table('customers')->insert(['full_name' => 'Lina', 'phone' => '700000010', 'email' => 'lina@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $this->expectException(QueryException::class);
        DB::table('customers')->insert(['full_name' => 'Rana', 'phone' => '700000010', 'email' => 'rana@example.test', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_customer_email_is_unique_when_non_null(): void
    {
        DB::table('customers')->insert(['full_name' => 'Lina', 'phone' => '700000011', 'email' => 'lina@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $this->expectException(QueryException::class);
        DB::table('customers')->insert(['full_name' => 'Rana', 'phone' => '700000012', 'email' => 'lina@example.test', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_service_rejects_negative_price_and_non_positive_duration(): void
    {
        $categoryId = $this->createCategory();

        try {
            DB::table('services')->insert(['service_category_id' => $categoryId, 'name' => 'Negative price', 'price' => -1, 'duration_minutes' => 30, 'created_at' => now(), 'updated_at' => now()]);
            $this->fail('A negative service price must be rejected.');
        } catch (QueryException) {
            // Expected: services_price_non_negative.
        }

        $this->expectException(QueryException::class);
        DB::table('services')->insert(['service_category_id' => $categoryId, 'name' => 'Zero duration', 'price' => 0, 'duration_minutes' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_time_ranges_and_duplicate_working_hours_are_rejected(): void
    {
        $employeeId = $this->createEmployee();
        DB::table('employee_working_hours')->insert(['employee_id' => $employeeId, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '17:00', 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(QueryException::class);
        DB::table('employee_working_hours')->insert(['employee_id' => $employeeId, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '17:00', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_appointment_rejects_end_at_before_or_equal_to_start_at(): void
    {
        [$employeeId, $customerId] = $this->createAppointmentParents();

        try {
            $this->createAppointment($employeeId, $customerId, 'pending', '2026-01-10 10:00:00+00', '2026-01-10 10:00:00+00');
            $this->fail('An appointment with equal start and end times must be rejected.');
        } catch (QueryException) {
            // Expected: appointments_time_range.
        }

        $this->expectException(QueryException::class);
        $this->createAppointment($employeeId, $customerId, 'pending', '2026-01-10 10:00:00+00', '2026-01-10 09:00:00+00');
    }

    public function test_active_appointments_for_one_employee_cannot_overlap(): void
    {
        [$employeeId, $customerId] = $this->createAppointmentParents();
        $this->createAppointment($employeeId, $customerId, 'confirmed', '2026-01-10 09:00:00+00', '2026-01-10 10:00:00+00');

        $this->expectException(QueryException::class);
        $this->createAppointment($employeeId, $customerId, 'pending', '2026-01-10 09:30:00+00', '2026-01-10 10:30:00+00');
    }

    public function test_adjacent_appointments_and_same_period_for_different_employees_are_allowed(): void
    {
        [$employeeId, $customerId] = $this->createAppointmentParents();
        $secondEmployeeId = $this->createEmployee();
        $this->createAppointment($employeeId, $customerId, 'pending', '2026-01-10 09:00:00+00', '2026-01-10 10:00:00+00');
        $this->createAppointment($employeeId, $customerId, 'confirmed', '2026-01-10 10:00:00+00', '2026-01-10 11:00:00+00');
        $this->createAppointment($secondEmployeeId, $customerId, 'pending', '2026-01-10 09:00:00+00', '2026-01-10 10:00:00+00');

        $this->assertSame(3, DB::table('appointments')->count());
    }

    public function test_cancelled_appointment_releases_its_time_range(): void
    {
        [$employeeId, $customerId] = $this->createAppointmentParents();
        $appointmentId = $this->createAppointment($employeeId, $customerId, 'pending', '2026-01-10 09:00:00+00', '2026-01-10 10:00:00+00');
        DB::table('appointments')->where('id', $appointmentId)->update(['status' => 'cancelled', 'updated_at' => now()]);

        $this->createAppointment($employeeId, $customerId, 'confirmed', '2026-01-10 09:00:00+00', '2026-01-10 10:00:00+00');
        $this->assertSame(2, DB::table('appointments')->count());
    }

    public function test_an_appointment_can_snapshot_multiple_services(): void
    {
        [$employeeId, $customerId] = $this->createAppointmentParents();
        $categoryId = $this->createCategory();
        $firstServiceId = $this->createService($categoryId, 'Cut', 30, 25);
        $secondServiceId = $this->createService($categoryId, 'Style', 45, 40);
        $appointmentId = $this->createAppointment($employeeId, $customerId, 'pending', '2026-01-10 09:00:00+00', '2026-01-10 10:15:00+00');

        DB::table('appointment_services')->insert([
            ['appointment_id' => $appointmentId, 'service_id' => $firstServiceId, 'booked_price' => 25, 'booked_duration_minutes' => 30, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['appointment_id' => $appointmentId, 'service_id' => $secondServiceId, 'booked_price' => 40, 'booked_duration_minutes' => 45, 'position' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('services')->where('id', $firstServiceId)->update(['price' => 99, 'duration_minutes' => 90, 'updated_at' => now()]);
        $snapshot = DB::table('appointment_services')->where('appointment_id', $appointmentId)->orderBy('position')->get(['booked_price', 'booked_duration_minutes'])->all();

        $this->assertSame('25.00', $snapshot[0]->booked_price);
        $this->assertSame(30, $snapshot[0]->booked_duration_minutes);
        $this->assertSame('40.00', $snapshot[1]->booked_price);
        $this->assertSame(45, $snapshot[1]->booked_duration_minutes);
    }

    private function createCategory(): int
    {
        return DB::table('service_categories')->insertGetId(['name' => 'Category '.Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function createEmployee(): int
    {
        return DB::table('employees')->insertGetId(['full_name' => 'Employee '.Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function createAppointmentParents(): array
    {
        return [$this->createEmployee(), DB::table('customers')->insertGetId(['full_name' => 'Customer '.Str::uuid(), 'phone' => '7'.random_int(10000000, 99999999), 'created_at' => now(), 'updated_at' => now()])];
    }

    private function createService(int $categoryId, string $name, int $duration, int $price): int
    {
        return DB::table('services')->insertGetId(['service_category_id' => $categoryId, 'name' => $name, 'price' => $price, 'duration_minutes' => $duration, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function createAppointment(int $employeeId, int $customerId, string $status, string $startAt, string $endAt): int
    {
        return DB::table('appointments')->insertGetId(['employee_id' => $employeeId, 'customer_id' => $customerId, 'status' => $status, 'start_at' => $startAt, 'end_at' => $endAt, 'created_at' => now(), 'updated_at' => now()]);
    }
}
