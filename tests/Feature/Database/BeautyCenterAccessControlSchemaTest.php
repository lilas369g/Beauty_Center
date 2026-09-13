<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class BeautyCenterAccessControlSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('pgsql', DB::connection()->getDriverName(), 'V2 schema tests must run against PostgreSQL.');
    }

    public function test_v2_tables_columns_foreign_keys_constraints_and_indexes_exist(): void
    {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename IN ('roles', 'permissions', 'role_permissions', 'users', 'auth_sessions', 'audit_logs')");
        $columns = DB::select("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name IN ('users', 'auth_sessions', 'audit_logs')");
        $constraints = collect(DB::select("SELECT conname FROM pg_constraint WHERE conname IN ('role_permissions_role_id_foreign', 'role_permissions_permission_id_foreign', 'users_role_id_foreign', 'users_employee_id_foreign', 'auth_sessions_user_id_foreign', 'audit_logs_actor_user_id_foreign', 'audit_logs_actor_type_valid', 'audit_logs_result_valid')"))->pluck('conname');
        $indexes = collect(DB::select("SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND indexname IN ('auth_sessions_user_revoked_expires_idx', 'audit_logs_entity_created_idx', 'audit_logs_actor_created_idx')"))->pluck('indexname');

        $this->assertCount(6, $tables);
        $this->assertContains(['table_name' => 'auth_sessions', 'column_name' => 'token_hash'], array_map(fn ($column) => (array) $column, $columns));
        $this->assertContains(['table_name' => 'audit_logs', 'column_name' => 'old_values'], array_map(fn ($column) => (array) $column, $columns));
        $this->assertCount(8, $constraints);
        $this->assertSame(['audit_logs_actor_created_idx', 'audit_logs_entity_created_idx', 'auth_sessions_user_revoked_expires_idx'], collect($indexes)->sort()->values()->all());
    }

    public function test_role_permission_user_and_session_unique_constraints_are_enforced(): void
    {
        $roleId = $this->createRole();
        $permissionId = DB::table('permissions')->insertGetId(['code' => 'appointments.view', 'name' => 'View appointments', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);

        try {
            DB::transaction(fn () => DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]));
            $this->fail('The role-permission composite key must reject duplicate grants.');
        } catch (QueryException) {
            // Expected: role_permissions_pkey.
        }

        $userId = $this->createUser($roleId, 'first@example.test');
        DB::table('auth_sessions')->insert([
            'user_id' => $userId,
            'token_hash' => hash('sha256', 'first-token'),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('auth_sessions')->insert([
            'user_id' => $userId,
            'token_hash' => hash('sha256', 'first-token'),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_role_slug_permission_code_and_user_email_are_unique(): void
    {
        DB::table('roles')->insert(['name' => 'Manager', 'slug' => 'manager', 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(QueryException::class);
        DB::table('roles')->insert(['name' => 'Other manager', 'slug' => 'manager', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_permission_code_and_user_email_are_unique(): void
    {
        $roleId = $this->createRole();
        DB::table('permissions')->insert(['code' => 'customers.view', 'name' => 'View customers', 'created_at' => now(), 'updated_at' => now()]);

        try {
            DB::transaction(fn () => DB::table('permissions')->insert(['code' => 'customers.view', 'name' => 'Duplicate', 'created_at' => now(), 'updated_at' => now()]));
            $this->fail('Permission codes must be unique.');
        } catch (QueryException) {
            // Expected: permissions_code_unique.
        }

        $this->createUser($roleId, 'unique@example.test');
        $this->expectException(QueryException::class);
        $this->createUser($roleId, 'unique@example.test');
    }

    public function test_employee_and_user_are_independently_optional_and_employee_link_is_unique(): void
    {
        $roleId = $this->createRole();
        $employeeId = DB::table('employees')->insertGetId(['full_name' => 'Employee '.Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
        $this->createUser($roleId, 'manager@example.test');
        $this->createUser($roleId, 'linked@example.test', $employeeId);

        $this->assertSame(1, DB::table('employees')->where('id', $employeeId)->count());

        $this->expectException(QueryException::class);
        $this->createUser($roleId, 'second-linked@example.test', $employeeId);
    }

    public function test_deleting_an_audit_actor_sets_the_reference_to_null_and_system_events_allow_no_actor(): void
    {
        $roleId = $this->createRole();
        $userId = $this->createUser($roleId, 'actor@example.test');
        $auditLogId = DB::table('audit_logs')->insertGetId([
            'actor_user_id' => $userId,
            'action' => 'appointments.update',
            'entity_type' => 'appointment',
            'entity_id' => 123,
            'old_values' => json_encode(['status' => 'pending']),
            'new_values' => json_encode(['status' => 'confirmed']),
            'created_at' => now(),
        ]);

        DB::table('users')->where('id', $userId)->delete();

        $this->assertNull(DB::table('audit_logs')->where('id', $auditLogId)->value('actor_user_id'));
        DB::table('audit_logs')->insert([
            'actor_type' => 'system',
            'action' => 'auth.login_failed',
            'result' => 'failed',
            'reason' => 'Unknown email',
            'created_at' => now(),
        ]);
        $this->assertSame(2, DB::table('audit_logs')->count());
    }

    public function test_audit_checks_and_no_jsonb_gin_index_are_enforced(): void
    {
        try {
            DB::table('audit_logs')->insert(['actor_type' => 'external', 'action' => 'invalid actor', 'created_at' => now()]);
            $this->fail('Only user and system audit actors are allowed.');
        } catch (QueryException) {
            // Expected: audit_logs_actor_type_valid.
        }

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->insert(['action' => 'invalid result', 'result' => 'unknown', 'created_at' => now()]);
    }

    public function test_audit_jsonb_fields_have_no_gin_index(): void
    {
        $ginIndexCount = DB::scalar("SELECT count(*) FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'audit_logs' AND indexdef ILIKE '% USING gin %'");

        $this->assertSame(0, (int) $ginIndexCount);
    }

    private function createRole(): int
    {
        return DB::table('roles')->insertGetId([
            'name' => 'Role '.Str::uuid(),
            'slug' => 'role-'.Str::lower(Str::uuid()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(int $roleId, string $email, ?int $employeeId = null): int
    {
        return DB::table('users')->insertGetId([
            'role_id' => $roleId,
            'employee_id' => $employeeId,
            'name' => 'User '.Str::uuid(),
            'email' => $email,
            'password' => Hash::make('test-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
