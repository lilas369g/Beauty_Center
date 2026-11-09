<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Staff authentication (Phase 1, item 1).
 *
 * Written from PROJECT.md, CLAUDE.md and docs/api/auth.md — NOT from the code.
 * A failing test means either the code misses a requirement, or a requirement
 * is wrong. Decide which before changing anything.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN = '/api/v1/auth/login';
    private const LOGOUT = '/api/v1/auth/logout';
    private const ME = '/api/v1/auth/me';

    /** Routes allowed without a token (CLAUDE.md §3). Add here only after updating PROJECT.md. */
    private const PUBLIC_ROUTES = [
        'POST api/v1/auth/login',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('pgsql', DB::connection()->getDriverName(), 'API tests must run against PostgreSQL.');
    }

    // ------------------------------------------------------------------
    // Login — success
    // ------------------------------------------------------------------

    public function test_login_with_valid_credentials_returns_a_bearer_token_and_the_user(): void
    {
        $user = $this->makeUser(['email' => 'manager@example.test', 'name' => 'Manager']);

        $response = $this->postJson(self::LOGIN, $this->credentials($user));

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.name', 'Manager')
            ->assertJsonPath('data.user.email', 'manager@example.test')
            ->assertJsonPath('data.user.is_active', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['name', 'email', 'is_active', 'last_login_at']]]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_response_never_exposes_the_password_or_numeric_ids(): void
    {
        $user = $this->makeUser();

        $userJson = $this->postJson(self::LOGIN, $this->credentials($user))->assertOk()->json('data.user');

        // CLAUDE.md §4: numeric ids are never exposed; passwords obviously not.
        foreach (['password', 'id', 'role_id', 'employee_id'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $userJson, "User payload must not contain '{$forbidden}'.");
        }
    }

    public function test_login_sets_last_login_at_to_now(): void
    {
        $this->freezeSecond();
        $user = $this->makeUser();
        $this->assertNull($user->last_login_at);

        $this->postJson(self::LOGIN, $this->credentials($user))->assertOk();

        $this->assertTrue(
            $user->fresh()->last_login_at?->equalTo(now()),
            'last_login_at must be set to the login time.'
        );
    }

    public function test_login_stores_device_name_as_the_token_name(): void
    {
        $user = $this->makeUser();

        $this->postJson(self::LOGIN, $this->credentials($user, ['device_name' => 'reception-pc']))->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'reception-pc',
        ]);
    }

    public function test_a_second_login_keeps_earlier_tokens_valid(): void
    {
        $user = $this->makeUser();

        $first = $this->login($user, 'web');
        $this->login($user, 'phone');

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->resetAuth();
        $this->withToken($first)->getJson(self::ME)->assertOk();
    }

    // ------------------------------------------------------------------
    // Login — failures
    // ------------------------------------------------------------------

    public function test_wrong_password_and_unknown_email_give_the_same_422_message(): void
    {
        $user = $this->makeUser(['email' => 'real@example.test']);

        $wrongPassword = $this->postJson(self::LOGIN, $this->credentials($user, ['password' => 'not-the-password']));
        $unknownEmail = $this->postJson(self::LOGIN, $this->credentials($user, ['email' => 'nobody@example.test']));

        $wrongPassword->assertStatus(422)->assertJsonValidationErrors(['email']);
        $unknownEmail->assertStatus(422)->assertJsonValidationErrors(['email']);

        // Same message, so an attacker cannot tell which emails exist.
        $this->assertSame($wrongPassword->json('errors.email.0'), $unknownEmail->json('errors.email.0'));
        $this->assertSame(__('auth.failed'), $wrongPassword->json('errors.email.0'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_user_with_correct_credentials_is_refused_without_a_token(): void
    {
        $user = $this->makeUser([], inactive: true);

        $response = $this->postJson(self::LOGIN, $this->credentials($user));

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->assertSame(__('auth.inactive'), $response->json('errors.email.0'));
        $this->assertNotSame(__('auth.failed'), __('auth.inactive'), 'The inactive message must differ from the generic one.');
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertNull($user->fresh()->last_login_at, 'A refused login must not update last_login_at.');
    }

    public function test_inactive_user_with_wrong_password_gets_the_generic_message(): void
    {
        // Otherwise a wrong password would reveal that an inactive account exists.
        $user = $this->makeUser([], inactive: true);

        $response = $this->postJson(self::LOGIN, $this->credentials($user, ['password' => 'not-the-password']));

        $response->assertStatus(422);
        $this->assertSame(__('auth.failed'), $response->json('errors.email.0'));
    }

    public function test_login_requires_all_fields(): void
    {
        $this->postJson(self::LOGIN, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_login_rejects_an_invalid_email_format(): void
    {
        $this->postJson(self::LOGIN, ['email' => 'not-an-email', 'password' => 'x', 'device_name' => 'web'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_enforces_the_documented_length_limits(): void
    {
        $tooLongEmail = Str::repeat('a', 250).'@example.test'; // > 255

        $this->postJson(self::LOGIN, [
            'email' => $tooLongEmail,
            'password' => Str::repeat('p', 256),
            'device_name' => Str::repeat('d', 151),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_login_accepts_a_device_name_exactly_at_the_limit(): void
    {
        $user = $this->makeUser();

        $this->postJson(self::LOGIN, $this->credentials($user, ['device_name' => Str::repeat('d', 150)]))
            ->assertOk();
    }

    // ------------------------------------------------------------------
    // Current user
    // ------------------------------------------------------------------

    public function test_me_returns_the_same_user_shape_as_login(): void
    {
        $user = $this->makeUser();
        $login = $this->postJson(self::LOGIN, $this->credentials($user))->assertOk();
        $this->resetAuth();

        $me = $this->withToken($login->json('data.token'))->getJson(self::ME)->assertOk();

        $this->assertEqualsCanonicalizing(
            array_keys($login->json('data.user')),
            array_keys($me->json('data')),
            'GET /auth/me must return the same user fields as the login response.'
        );
        $this->assertSame($user->email, $me->json('data.email'));
    }

    public function test_me_without_a_token_returns_the_translated_401(): void
    {
        $this->getJson(self::ME)
            ->assertUnauthorized()
            ->assertExactJson(['message' => __('auth.unauthenticated')]);
    }

    public function test_me_with_a_fake_token_returns_401(): void
    {
        $this->withToken('999|this-token-does-not-exist')->getJson(self::ME)->assertUnauthorized();
    }

    public function test_a_request_without_accept_json_still_gets_401_not_500(): void
    {
        // CLAUDE.md §4: the user never sees a 500.
        $this->get(self::ME)->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Token expiry
    // ------------------------------------------------------------------

    public function test_tokens_have_an_expiry_configured(): void
    {
        $this->assertNotNull(config('sanctum.expiration'), 'Tokens must not live forever.');
        $this->assertGreaterThan(0, (int) config('sanctum.expiration'));
    }

    public function test_a_token_works_before_expiry_and_returns_401_after_it(): void
    {
        config(['sanctum.expiration' => 60]);
        $token = $this->login($this->makeUser());

        $this->travel(59)->minutes();
        $this->resetAuth();
        $this->withToken($token)->getJson(self::ME)->assertOk();

        $this->travel(2)->minutes();
        $this->resetAuth();
        $this->withToken($token)->getJson(self::ME)->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Logout
    // ------------------------------------------------------------------

    public function test_logout_deletes_only_the_current_token(): void
    {
        $user = $this->makeUser();
        $webToken = $this->login($user, 'web');
        $phoneToken = $this->login($user, 'phone');

        $this->resetAuth();
        $this->withToken($webToken)->postJson(self::LOGOUT)->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'web']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'phone']);

        $this->resetAuth();
        $this->withToken($webToken)->getJson(self::ME)->assertUnauthorized();

        $this->resetAuth();
        $this->withToken($phoneToken)->getJson(self::ME)->assertOk();
    }

    public function test_logout_without_a_token_returns_401(): void
    {
        $this->postJson(self::LOGOUT)->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Deactivation (staff.manage — "إيقافهن")
    // ------------------------------------------------------------------

    public function test_deactivating_a_user_cuts_off_her_existing_tokens(): void
    {
        // Not stated in docs/api/auth.md, but required by PROJECT.md §9:
        // stopping a staff member must actually stop her access.
        $user = $this->makeUser();
        $token = $this->login($user);

        $user->forceFill(['is_active' => false])->save();

        $this->resetAuth();
        $this->withToken($token)->getJson(self::ME)->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Every API route is protected (CLAUDE.md §3)
    // ------------------------------------------------------------------

    public function test_every_v1_route_except_the_public_list_requires_sanctum(): void
    {
        $unprotected = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => Str::startsWith($route->uri(), 'api/v1/'))
            ->flatMap(fn (Route $route) => collect($route->methods())
                ->reject(fn (string $method) => $method === 'HEAD')
                ->map(fn (string $method) => [
                    'key' => $method.' '.$route->uri(),
                    'protected' => in_array('auth:sanctum', $route->gatherMiddleware(), true),
                ]))
            ->reject(fn (array $r) => in_array($r['key'], self::PUBLIC_ROUTES, true))
            ->reject(fn (array $r) => $r['protected'])
            ->pluck('key')
            ->values()
            ->all();

        $this->assertSame([], $unprotected, 'These routes are reachable without a token: '.implode(', ', $unprotected));
    }

    public function test_service_and_category_endpoints_return_401_without_a_token(): void
    {
        $fakeUuid = (string) Str::uuid();

        $this->getJson('/api/v1/services')->assertUnauthorized();
        $this->postJson('/api/v1/services', [])->assertUnauthorized();
        $this->getJson('/api/v1/service-categories')->assertUnauthorized();
        $this->postJson('/api/v1/service-categories', [])->assertUnauthorized();
        $this->deleteJson("/api/v1/service-categories/{$fakeUuid}")->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Language (BR-41)
    // ------------------------------------------------------------------

    public function test_auth_messages_exist_in_arabic_and_english(): void
    {
        foreach (['failed', 'inactive', 'unauthenticated'] as $key) {
            $en = trans("auth.{$key}", [], 'en');
            $ar = trans("auth.{$key}", [], 'ar');

            $this->assertNotSame("auth.{$key}", $en, "Missing English message auth.{$key}");
            $this->assertNotSame("auth.{$key}", $ar, "Missing Arabic message auth.{$key}");
            $this->assertNotSame($en, $ar, "auth.{$key} is not translated to Arabic");
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeUser(array $attributes = [], bool $inactive = false): User
    {
        $factory = UserFactory::new();

        return ($inactive ? $factory->inactive() : $factory)->create($attributes);
    }

    private function credentials(User $user, array $overrides = []): array
    {
        return array_merge([
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'web',
        ], $overrides);
    }

    private function login(User $user, string $device = 'web'): string
    {
        $this->resetAuth();

        return $this->postJson(self::LOGIN, $this->credentials($user, ['device_name' => $device]))
            ->assertOk()
            ->json('data.token');
    }

    /**
     * Laravel keeps the authenticated user in memory between requests inside one test.
     * Real clients don't share that memory, so forget it before each new request.
     */
    private function resetAuth(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
