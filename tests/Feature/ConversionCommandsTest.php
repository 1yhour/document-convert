<?php

namespace Tests\Feature;

use App\Models\Conversion;
use App\Models\User;
use App\Services\CloudConvertService;
use CloudConvert\Models\Job;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ConversionCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sync_stuck_command_syncs_finished_and_failed_jobs(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        // 1. Stuck conversion that finished on CloudConvert
        $stuckFinished = Conversion::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
            'progress' => 50,
            'cloudconvert_job_id' => 'stuck-cc-finished',
            'updated_at' => now()->subMinutes(30),
        ]);

        // 2. Stuck conversion that errored on CloudConvert
        $stuckErrored = Conversion::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
            'progress' => 50,
            'cloudconvert_job_id' => 'stuck-cc-error',
            'updated_at' => now()->subMinutes(30),
        ]);

        // Mock finished job
        $finishedJob = Mockery::mock(Job::class);
        $finishedJob->shouldReceive('getStatus')->andReturn(Job::STATUS_FINISHED);

        // Mock error job
        $errorJob = Mockery::mock(Job::class);
        $errorJob->shouldReceive('getStatus')->andReturn(Job::STATUS_ERROR);

        $mockService = Mockery::mock(CloudConvertService::class);
        $mockService->shouldReceive('getJob')->with('stuck-cc-finished')->andReturn($finishedJob);
        $mockService->shouldReceive('getExportUrl')->with($finishedJob)->andReturn('https://cloudconvert.mock/download.docx');
        $mockService->shouldReceive('downloadExportFile')->once()->andReturn(true);

        $mockService->shouldReceive('getJob')->with('stuck-cc-error')->andReturn($errorJob);

        $this->app->instance(CloudConvertService::class, $mockService);

        $this->artisan('conversions:sync-stuck', ['--minutes' => 15])
            ->assertSuccessful();

        $stuckFinished->refresh();
        $this->assertEquals('completed', $stuckFinished->status);
        $this->assertEquals(100, $stuckFinished->progress);

        $stuckErrored->refresh();
        $this->assertEquals('failed', $stuckErrored->status);
        $this->assertEquals(100, $stuckErrored->progress);
    }

    public function test_cleanup_command_removes_old_files_and_clears_paths(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $oldInput = 'conversions/inputs/old-file.pdf';
        $oldOutput = 'conversions/outputs/old-file.docx';
        Storage::disk('local')->put($oldInput, 'old input binary');
        Storage::disk('local')->put($oldOutput, 'old output binary');

        $oldConversion = Conversion::factory()->create([
            'user_id' => $user->id,
            'input_path' => $oldInput,
            'output_path' => $oldOutput,
            'created_at' => now()->subHours(25),
        ]);

        $recentInput = 'conversions/inputs/recent-file.pdf';
        Storage::disk('local')->put($recentInput, 'recent binary');
        $recentConversion = Conversion::factory()->create([
            'user_id' => $user->id,
            'input_path' => $recentInput,
            'created_at' => now()->subHours(2),
        ]);

        $this->artisan('conversions:cleanup', ['--hours' => 24])
            ->assertSuccessful();

        // Old files should be deleted and paths cleared
        Storage::disk('local')->assertMissing($oldInput);
        Storage::disk('local')->assertMissing($oldOutput);

        $oldConversion->refresh();
        $this->assertNull($oldConversion->input_path);
        $this->assertNull($oldConversion->output_path);

        // Recent files should remain untouched
        Storage::disk('local')->assertExists($recentInput);
        $recentConversion->refresh();
        $this->assertNotNull($recentConversion->input_path);
    }
}
