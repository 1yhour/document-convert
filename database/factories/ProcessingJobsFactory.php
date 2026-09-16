<?php

namespace Database\Factories;

use App\Models\Documents;
use App\Models\Operations;
use App\Models\ProcessingJobs;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessingJobs>
 */
class ProcessingJobsFactory extends Factory
{
    protected $model = ProcessingJobs::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'queued', 'processing', 'completed', 'failed', 'cancelled']);

        return [
            'document_id' => Documents::factory(),
            'user_id' => function (array $attributes) {
                return Documents::find($attributes['document_id'])?->user_id ?? User::factory();
            },
            'operation_id' => Operations::factory(),
            'status' => $status,
            'progress' => $status === 'completed' ? 100 : fake()->numberBetween(0, 90),
            'attempts' => fake()->numberBetween(0, 3),
            'error_message' => $status === 'failed' ? 'Operation failed due to processing error.' : null,
            'started_at' => in_array($status, ['processing', 'completed', 'failed']) ? now()->subMinutes(2) : null,
            'completed_at' => in_array($status, ['completed', 'failed', 'cancelled']) ? now() : null,
        ];
    }
}
