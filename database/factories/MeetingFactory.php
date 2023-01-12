<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'meeting_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'student_id' => \App\Models\Student::factory(),
            'supervisor_id' => \App\Models\User::factory(),
        ];
    }
}
