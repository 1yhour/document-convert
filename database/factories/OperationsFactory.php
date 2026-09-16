<?php

namespace Database\Factories;

use App\Models\Operations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Operations>
 */
class OperationsFactory extends Factory
{
    protected $model = Operations::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $input = fake()->randomElement(['docx', 'png', 'xlsx', 'txt', 'html']);
        $output = 'pdf';
        $code = strtolower($input . '_to_' . $output . '_' . fake()->unique()->numberBetween(100, 99999));

        return [
            'code' => $code,
            'name' => strtoupper($input) . ' to ' . strtoupper($output),
            'input_format' => $input,
            'output_format' => $output,
            'cc_convert_engine' => 'office',
            'is_active' => true,
        ];
    }
}
