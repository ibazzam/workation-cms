<?php

namespace Database\Factories;

use App\Models\Workation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workation>
 */
class WorkationFactory extends Factory
{
    protected $model = Workation::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+30 days');
        $endDate = (clone $startDate)->modify('+7 days');

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->city(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'price' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
