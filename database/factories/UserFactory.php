<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'branch_id' => null,
            'fullname' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'password_hash' => Hash::make('password'),
            'member_code' => strtoupper(fake()->bothify('MBR-#####')),
            'avatar_path' => null,
            'avatar_is_external' => false,
            'profile_type_id' => 'a1b2c3d4-0001-4000-8000-000000000001',
            'is_verified' => true,
            'is_active' => true,
        ];
    }
}
