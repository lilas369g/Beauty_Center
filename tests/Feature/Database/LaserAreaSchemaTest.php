<?php

namespace Tests\Feature\Database;

use App\Models\AppointmentServiceLaserArea;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LaserAreaSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('pgsql', DB::connection()->getDriverName(), 'Laser schema tests must run against PostgreSQL.');
    }

    public function test_laser_schema_has_the_required_tables_constraints_and_only_the_required_performer_index(): void
    {
        $tables = collect(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename IN ('laser_areas', 'laser_service_settings', 'laser_service_areas', 'appointment_service_laser_areas')"))->pluck('tablename')->sort()->values()->all();
        $constraints = collect(DB::select("SELECT conname FROM pg_constraint WHERE conname IN ('appointment_services_id_unique', 'laser_service_settings_service_id_unique', 'laser_service_areas_setting_area_unique', 'appointment_service_laser_areas_appointment_service_area_unique', 'laser_service_areas_default_price_non_negative', 'laser_service_areas_default_duration_positive', 'appointment_service_laser_areas_status_valid', 'appointment_service_laser_areas_price_non_negative', 'appointment_service_laser_areas_duration_positive', 'appointment_service_laser_areas_appointment_service_id_foreign', 'appointment_service_laser_areas_laser_service_area_id_foreign', 'appointment_service_laser_areas_performed_by_employee_id_foreign')"))->pluck('conname')->sort()->values()->all();
        $indexes = collect(DB::select("SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'appointment_service_laser_areas'"))->pluck('indexname')->all();
        $forbiddenColumns = DB::scalar("SELECT count(*) FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'appointment_service_laser_areas' AND column_name IN ('customer_id', 'user_id', 'appointment_id')");

        $this->assertSame(['appointment_service_laser_areas', 'laser_areas', 'laser_service_areas', 'laser_service_settings'], $tables);
        $this->assertCount(12, $constraints);
        $this->assertContains('appointment_service_laser_areas_performed_by_employee_idx', $indexes);
        $this->assertSame(0, (int) $forbiddenColumns);
    }

    public function test_laser_setting_is_unique_per_service_and_non_laser_services_need_no_setting(): void
    {
        $categoryId = $this->createCategory();
        $nailsServiceId = $this->createService($categoryId, 'Nails');
        $laserServiceId = $this->createService($categoryId, 'Laser');

        $this->assertSame(0, DB::table('laser_service_settings')->where('service_id', $nailsServiceId)->count());
        DB::table('laser_service_settings')->insert(['service_id' => $laserServiceId, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertQueryFails(fn () => DB::table('laser_service_settings')->insert([
            'service_id' => $laserServiceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_same_area_cannot_be_configured_twice_for_one_laser_service(): void
    {
        [$settingId, $areaId] = $this->createLaserConfiguration();
        DB::table('laser_service_areas')->insert($this->laserServiceAreaData($settingId, $areaId));

        $this->assertQueryFails(fn () => DB::table('laser_service_areas')->insert($this->laserServiceAreaData($settingId, $areaId)));
    }

    public function test_laser_defaults_and_history_snapshots_enforce_their_checks(): void
    {
        [$settingId, $areaId] = $this->createLaserConfiguration();

        $this->assertQueryFails(fn () => DB::table('laser_service_areas')->insert(array_merge(
            $this->laserServiceAreaData($settingId, $areaId),
            ['default_price' => -1]
        )));
        $this->assertQueryFails(fn () => DB::table('laser_service_areas')->insert(array_merge(
            $this->laserServiceAreaData($settingId, $areaId),
            ['default_duration_minutes' => 0]
        )));

        $laserServiceAreaId = DB::table('laser_service_areas')->insertGetId($this->laserServiceAreaData($settingId, $areaId));
        $appointmentServiceId = $this->createBookedLaserService($this->settingServiceId($settingId));

        $this->assertQueryFails(fn () => DB::table('appointment_service_laser_areas')->insert(array_merge(
            $this->appointmentAreaData($appointmentServiceId, $laserServiceAreaId),
            ['status' => 'invalid']
        )));
        $this->assertQueryFails(fn () => DB::table('appointment_service_laser_areas')->insert(array_merge(
            $this->appointmentAreaData($appointmentServiceId, $laserServiceAreaId),
            ['price_snapshot' => -1]
        )));
        $this->assertQueryFails(fn () => DB::table('appointment_service_laser_areas')->insert(array_merge(
            $this->appointmentAreaData($appointmentServiceId, $laserServiceAreaId),
            ['duration_snapshot_minutes' => 0]
        )));
    }

    public function test_same_configured_area_cannot_be_selected_twice_for_one_booked_service(): void
    {
        [$settingId, $areaId] = $this->createLaserConfiguration();
        $laserServiceAreaId = DB::table('laser_service_areas')->insertGetId($this->laserServiceAreaData($settingId, $areaId));
        $appointmentServiceId = $this->createBookedLaserService($this->settingServiceId($settingId));
        DB::table('appointment_service_laser_areas')->insert($this->appointmentAreaData($appointmentServiceId, $laserServiceAreaId));

        $this->assertQueryFails(fn () => DB::table('appointment_service_laser_areas')->insert($this->appointmentAreaData($appointmentServiceId, $laserServiceAreaId)));
    }

    public function test_history_relations_are_restrictive_and_deactivation_keeps_history(): void
    {
        [$settingId, $areaId] = $this->createLaserConfiguration();
        $laserServiceAreaId = DB::table('laser_service_areas')->insertGetId($this->laserServiceAreaData($settingId, $areaId));
        $appointmentServiceId = $this->createBookedLaserService($this->settingServiceId($settingId));
        $performerId = $this->createEmployee();
        $historyId = DB::table('appointment_service_laser_areas')->insertGetId(array_merge(
            $this->appointmentAreaData($appointmentServiceId, $laserServiceAreaId),
            ['performed_by_employee_id' => $performerId, 'status' => 'completed']
        ));

        $this->assertQueryFails(fn () => DB::table('laser_service_areas')->where('id', $laserServiceAreaId)->delete());
        $this->assertQueryFails(fn () => DB::table('laser_areas')->where('id', $areaId)->delete());
        $this->assertQueryFails(fn () => DB::table('employees')->where('id', $performerId)->delete());

        DB::table('laser_areas')->where('id', $areaId)->update(['is_active' => false, 'updated_at' => now()]);
        DB::table('laser_service_areas')->where('id', $laserServiceAreaId)->update(['is_active' => false, 'updated_at' => now()]);

        $this->assertFalse((bool) DB::table('laser_areas')->where('id', $areaId)->value('is_active'));
        $this->assertFalse((bool) DB::table('laser_service_areas')->where('id', $laserServiceAreaId)->value('is_active'));
        $this->assertSame($historyId, DB::table('appointment_service_laser_areas')->where('id', $historyId)->value('id'));
    }

    public function test_eloquent_relations_follow_the_booked_service_chain(): void
    {
        [$settingId, $areaId] = $this->createLaserConfiguration();
        $laserServiceAreaId = DB::table('laser_service_areas')->insertGetId($this->laserServiceAreaData($settingId, $areaId));
        $appointmentServiceId = $this->createBookedLaserService($this->settingServiceId($settingId));
        $historyId = DB::table('appointment_service_laser_areas')->insertGetId($this->appointmentAreaData($appointmentServiceId, $laserServiceAreaId));

        $history = AppointmentServiceLaserArea::query()
            ->with('appointmentService.laserAreas', 'laserServiceArea.setting.service', 'laserServiceArea.area')
            ->findOrFail($historyId);

        $this->assertSame($appointmentServiceId, $history->appointmentService->id);
        $this->assertSame($laserServiceAreaId, $history->laserServiceArea->id);
        $this->assertSame($areaId, $history->laserServiceArea->area->id);
        $this->assertSame(1, $history->appointmentService->laserAreas->count());
    }

    private function createLaserConfiguration(): array
    {
        $categoryId = $this->createCategory();
        $serviceId = $this->createService($categoryId, 'Laser '.Str::uuid());
        $settingId = DB::table('laser_service_settings')->insertGetId(['service_id' => $serviceId, 'created_at' => now(), 'updated_at' => now()]);
        $areaId = DB::table('laser_areas')->insertGetId(['code' => 'full-body-'.Str::lower(Str::uuid()), 'name' => 'Full body', 'created_at' => now(), 'updated_at' => now()]);

        return [$settingId, $areaId];
    }

    private function createBookedLaserService(?int $serviceId = null): int
    {
        $employeeId = $this->createEmployee();
        $customerId = DB::table('customers')->insertGetId([
            'full_name' => 'Customer '.Str::uuid(),
            'phone' => '7'.random_int(10000000, 99999999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $appointmentId = DB::table('appointments')->insertGetId([
            'customer_id' => $customerId,
            'employee_id' => $employeeId,
            'start_at' => '2026-09-08 09:00:00+00',
            'end_at' => '2026-09-08 10:00:00+00',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($serviceId === null) {
            $categoryId = $this->createCategory();
            $serviceId = $this->createService($categoryId, 'Booked laser '.Str::uuid());
        }

        return DB::table('appointment_services')->insertGetId([
            'appointment_id' => $appointmentId,
            'service_id' => $serviceId,
            'booked_price' => 100,
            'booked_duration_minutes' => 60,
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCategory(): int
    {
        return DB::table('service_categories')->insertGetId([
            'name' => 'Category '.Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createService(int $categoryId, string $name): int
    {
        return DB::table('services')->insertGetId([
            'service_category_id' => $categoryId,
            'name' => $name,
            'price' => 100,
            'duration_minutes' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEmployee(): int
    {
        return DB::table('employees')->insertGetId([
            'full_name' => 'Employee '.Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function laserServiceAreaData(int $settingId, int $areaId): array
    {
        return [
            'laser_service_setting_id' => $settingId,
            'laser_area_id' => $areaId,
            'default_price' => 50,
            'default_duration_minutes' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function appointmentAreaData(int $appointmentServiceId, int $laserServiceAreaId): array
    {
        return [
            'appointment_service_id' => $appointmentServiceId,
            'laser_service_area_id' => $laserServiceAreaId,
            'area_name_snapshot' => 'Full body',
            'price_snapshot' => 50,
            'duration_snapshot_minutes' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function assertQueryFails(callable $query): void
    {
        try {
            DB::transaction($query);
            $this->fail('The database operation should have violated a schema constraint.');
        } catch (QueryException) {
            // Expected: the nested transaction rolls back its PostgreSQL savepoint.
        }
    }

    private function settingServiceId(int $settingId): int
    {
        return (int) DB::table('laser_service_settings')->where('id', $settingId)->value('service_id');
    }
}
