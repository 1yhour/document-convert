<?php

namespace Database\Factories;

use App\Models\JobResults;
use App\Models\ProcessingJobs;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobResults>
 */
class JobResultsFactory extends Factory
{
    protected $model = JobResults::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'processing_job_id' => ProcessingJobs::factory(),
            'result_path' => 'results/' . Str::uuid() . '.pdf',
            'result_mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 5242880),
            'external_task_id' => 'task_' . Str::random(16),
            'external_document_url' => fake()->url(),
        ];
    }
}
