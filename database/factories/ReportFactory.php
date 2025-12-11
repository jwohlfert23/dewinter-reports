<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_name' => fake()->company(),
            'client_logo_path' => null,
            'date' => fake()->date(),
            'position_title' => fake()->jobTitle(),
            'google_sheet_url' => 'https://docs.google.com/spreadsheets/d/'.fake()->uuid().'/edit',
        ];
    }
}
