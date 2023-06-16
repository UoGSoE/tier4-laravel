<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->randomNumber(7).$this->faker->randomLetter(),
            'email' => $this->faker->unique()->safeEmail(),
            'forenames' => $this->faker->firstName(),
            'surname' => $this->faker->lastName(),
            'type' => \App\Models\Student::TYPE_PHD,
            'supervisor_id' => User::factory(),
            'is_active' => true,
            'is_silenced' => false,
        ];
    }

    public function postgradProject()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => \App\Models\Student::TYPE_POSTGRAD_PROJECT,
                'sub_type' => $this->faker->randomElement([\App\Models\Student::SUB_TYPE_MSC, \App\Models\Student::SUB_TYPE_BMENG]),
            ];
        });
    }

    public function silenced()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_silenced' => true,
                'silenced_reason' => $this->faker->sentence(),
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
