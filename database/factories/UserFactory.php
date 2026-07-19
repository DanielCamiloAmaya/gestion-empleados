<?php

namespace Database\Factories;

use App\Models\Departamento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'employee_code' => 'EMP-'.fake()->unique()->numerify('#####'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'departamento_id' => fn () => Departamento::query()->inRandomOrder()->value('id'),
            'job_title' => fake()->jobTitle(),
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->city(),
            'email' => fake()->unique()->safeEmail(),
            'username' => Str::lower(fake()->unique()->userName()),
            'email_verified_at' => now(),
            'password' => 'SecurePassword1!',
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['employment_status' => 'inactive']);
    }
}
