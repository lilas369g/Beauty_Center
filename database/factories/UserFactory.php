<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Staff user factory.
 *
 * The User model does not use HasFactory, so call it as:
 *   UserFactory::new()->create();
 *   UserFactory::new()->inactive()->create();
 *
 * A role row is created on the fly, so no Role model is required.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'role_id' => fn () => DB::table('roles')->insertGetId([
                'name' => 'Role '.Str::random(8),
                'slug' => 'role-'.Str::lower(Str::random(12)),
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'employee_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password', // hashed by the model's "hashed" cast
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
