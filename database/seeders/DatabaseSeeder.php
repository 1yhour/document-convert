<?php

namespace Database\Seeders;

use App\Models\Documents;
use App\Models\JobResults;
use App\Models\JobStatusHistories;
use App\Models\Operations;
use App\Models\ProcessingJobs;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed supported operations
        $this->call(OperationsSeeder::class);

        // 2. Create demo user
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
            ]
        );

        // 3. Create extra test users
        $users = User::factory(3)->create();
        $allUsers = $users->concat([$demoUser]);

        $availableOperations = Operations::all();

        // 4. Create documents and jobs for users
        foreach ($allUsers as $user) {
            $documents = Documents::factory(2)->create([
                'user_id' => $user->id,
            ]);

            foreach ($documents as $document) {
                $operation = $availableOperations->random();

                // Create a completed job
                $completedJob = ProcessingJobs::factory()->create([
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'operation_id' => $operation->id,
                    'status' => 'completed',
                    'progress' => 100,
                    'attempts' => 1,
                    'started_at' => now()->subMinutes(5),
                    'completed_at' => now()->subMinutes(1),
                ]);

                // Create job result for completed job
                JobResults::factory()->create([
                    'processing_job_id' => $completedJob->id,
                ]);

                // Create status histories
                JobStatusHistories::factory()->create([
                    'processing_job_id' => $completedJob->id,
                    'from_status' => 'pending',
                    'to_status' => 'processing',
                    'external_status' => 'task.started',
                    'note' => 'Worker picked up job.',
                ]);

                JobStatusHistories::factory()->create([
                    'processing_job_id' => $completedJob->id,
                    'from_status' => 'processing',
                    'to_status' => 'completed',
                    'external_status' => 'task.finished',
                    'note' => 'Conversion finished successfully.',
                ]);

                // Create a pending or processing job
                $pendingJob = ProcessingJobs::factory()->create([
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'operation_id' => $operation->id,
                    'status' => 'processing',
                    'progress' => 45,
                    'attempts' => 1,
                    'started_at' => now()->subSeconds(30),
                    'completed_at' => null,
                ]);

                JobStatusHistories::factory()->create([
                    'processing_job_id' => $pendingJob->id,
                    'from_status' => 'pending',
                    'to_status' => 'processing',
                    'external_status' => 'task.started',
                    'note' => 'Conversion in progress.',
                ]);
            }
        }
    }
}
