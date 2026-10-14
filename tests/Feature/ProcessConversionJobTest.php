<?php

namespace Tests\Feature;

use App\Jobs\ProcessConversionJob;
use App\Models\Conversion;
use App\Models\User;
use App\Services\CloudConvertService;
use CloudConvert\Models\Job;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProcessConversionJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_job_progresses_through_milestones_and_uploads_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $inputPath = 'conversions/inputs/test-file.pdf';
        Storage::disk('local')->put($inputPath, 'fake-pdf-content');

        $conversion = Conversion::factory()->create([
            'user_id' => $user->id,
            'operation' => 'pdf_to_docx',
            'original_name' => 'document.pdf',
            'input_path' => $inputPath,
            'status' => 'pending',
            'progress' => 0,
        ]);

        $mockJob = Mockery::mock(Job::class);
        $mockJob->shouldReceive('getId')->andReturn('cloudconvert-job-uuid-123');

        $mockService = Mockery::mock(CloudConvertService::class);
        $mockService->shouldReceive('createJob')
            ->once()
            ->with($conversion->id, 'pdf', 'docx')
            ->andReturn($mockJob);

        $mockService->shouldReceive('uploadFile')
            ->once()
            ->with($mockJob, Mockery::any(), 'document.pdf')
            ->andReturnNull();

        $queueJob = new ProcessConversionJob($conversion);
        $queueJob->handle($mockService);

        $conversion->refresh();

        $this->assertEquals('cloudconvert-job-uuid-123', $conversion->cloudconvert_job_id);
        $this->assertEquals('processing', $conversion->status);
        $this->assertEquals(50, $conversion->progress);
    }

    public function test_job_handles_exception_and_marks_status_failed(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $inputPath = 'conversions/inputs/test-file.pdf';
        Storage::disk('local')->put($inputPath, 'fake-pdf-content');

        $conversion = Conversion::factory()->create([
            'user_id' => $user->id,
            'operation' => 'pdf_to_docx',
            'original_name' => 'document.pdf',
            'input_path' => $inputPath,
            'status' => 'pending',
            'progress' => 0,
        ]);

        $mockService = Mockery::mock(CloudConvertService::class);
        $mockService->shouldReceive('createJob')
            ->once()
            ->andThrow(new \Exception('CloudConvert API connection timeout'));

        $queueJob = new ProcessConversionJob($conversion);

        try {
            $queueJob->handle($mockService);
            $this->fail('Expected exception was not thrown.');
        } catch (\Throwable $e) {
            $this->assertEquals('CloudConvert API connection timeout', $e->getMessage());
        }

        $conversion->refresh();

        $this->assertEquals('failed', $conversion->status);
        // Error message shown to user must be safe and user-friendly, not leaking raw exceptions
        $this->assertEquals('Conversion failed during processing. Please try again later.', $conversion->error_message);
    }
}
