<?php

namespace Database\Factories;

use App\Models\JobStatusHistories;
use App\Models\ProcessingJobs;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobStatusHistories>
 */
class JobStatusHistoriesFactory extends Factory
{
    protected $model = JobStatusHistories::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'processing_job_id' => ProcessingJobs::factory(),
            'from_status' => 'pending',
            'to_status' => 'processing',
            'external_status' => 'job.started',
            'note' => fake()->sentence(),
        ];
    }
}
