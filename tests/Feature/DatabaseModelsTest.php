<?php

namespace Tests\Feature;

use App\Models\Documents;
use App\Models\JobResults;
use App\Models\JobStatusHistories;
use App\Models\Operations;
use App\Models\ProcessingJobs;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_factories_create_valid_records_with_uuids(): void
    {
        // 1. User Factory
        $user = User::factory()->create();
        $this->assertTrue(Str::isUuid($user->id));
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        // 2. Documents Factory
        $document = Documents::factory()->create(['user_id' => $user->id]);
        $this->assertTrue(Str::isUuid($document->id));
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'user_id' => $user->id]);

        // 3. Operations Factory
        $operation = Operations::factory()->create();
        $this->assertTrue(Str::isUuid($operation->id));
        $this->assertDatabaseHas('operations', ['id' => $operation->id]);

        // 4. ProcessingJobs Factory
        $job = ProcessingJobs::factory()->create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'operation_id' => $operation->id,
        ]);
        $this->assertTrue(Str::isUuid($job->id));
        $this->assertDatabaseHas('processing_jobs', ['id' => $job->id]);

        // 5. JobResults Factory
        $result = JobResults::factory()->create(['processing_job_id' => $job->id]);
        $this->assertTrue(Str::isUuid($result->id));
        $this->assertDatabaseHas('job_results', ['id' => $result->id, 'processing_job_id' => $job->id]);

        // 6. JobStatusHistories Factory
        $history = JobStatusHistories::factory()->create(['processing_job_id' => $job->id]);
        $this->assertTrue(Str::isUuid($history->id));
        $this->assertDatabaseHas('job_status_histories', ['id' => $history->id, 'processing_job_id' => $job->id]);
    }

    public function test_all_eloquent_relationships_work_correctly(): void
    {
        $user = User::factory()->create();
        $document = Documents::factory()->create(['user_id' => $user->id]);
        $operation = Operations::factory()->create();
        $job = ProcessingJobs::factory()->create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'operation_id' => $operation->id,
        ]);
        $result = JobResults::factory()->create(['processing_job_id' => $job->id]);
        $history = JobStatusHistories::factory()->create(['processing_job_id' => $job->id]);

        // Document relationships
        $this->assertEquals($user->id, $document->user->id);
        $this->assertTrue($document->processingJobs->contains($job));

        // User relationships
        $this->assertTrue($user->documents->contains($document));
        $this->assertTrue($user->processingJobs->contains($job));

        // Operation relationships
        $this->assertTrue($operation->processingJobs->contains($job));

        // ProcessingJob relationships
        $this->assertEquals($document->id, $job->document->id);
        $this->assertEquals($user->id, $job->user->id);
        $this->assertEquals($operation->id, $job->operation->id);
        $this->assertEquals($result->id, $job->result->id);
        $this->assertTrue($job->statusHistories->contains($history));

        // Inverse relationships
        $this->assertEquals($job->id, $result->processingJob->id);
        $this->assertEquals($job->id, $history->processingJob->id);
    }

    public function test_database_seeder_runs_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, User::count());
        $this->assertGreaterThan(0, Operations::count());
        $this->assertGreaterThan(0, Documents::count());
        $this->assertGreaterThan(0, ProcessingJobs::count());
        $this->assertGreaterThan(0, JobResults::count());
        $this->assertGreaterThan(0, JobStatusHistories::count());
    }
}
