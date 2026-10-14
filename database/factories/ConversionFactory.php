<?php

namespace Database\Factories;

use App\Models\Conversion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversion>
 */
class ConversionFactory extends Factory
{
    protected $model = Conversion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'operation' => 'pdf_to_docx',
            'original_name' => fake()->word().'.pdf',
            'input_path' => 'conversions/inputs/'.Str::uuid().'.pdf',
            'output_path' => null,
            'status' => 'pending',
            'progress' => 0,
            'cloudconvert_job_id' => null,
            'error_message' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
            'output_path' => 'conversions/outputs/'.Str::uuid().'.docx',
            'cloudconvert_job_id' => (string) Str::uuid(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'progress' => 100,
            'cloudconvert_job_id' => (string) Str::uuid(),
            'error_message' => 'Conversion failed.',
        ]);
    }
}
