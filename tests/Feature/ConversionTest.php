<?php

namespace Tests\Feature;

use App\Jobs\ProcessConversionJob;
use App\Models\Conversion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConversionTest extends TestCase
{
    use RefreshDatabase;

    protected User $userWithPermissions;

    protected User $userWithoutPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->userWithPermissions = User::factory()->create();
        $this->userWithPermissions->assignRole('user');

        $this->userWithoutPermissions = User::factory()->create();
        // Has no roles or permissions
    }

    public function test_guest_cannot_access_conversions_endpoints(): void
    {
        $conversion = Conversion::factory()->create();

        $this->getJson('/api/conversions')->assertStatus(401);
        $this->postJson('/api/conversions', [])->assertStatus(401);
        $this->getJson("/api/conversions/{$conversion->id}")->assertStatus(401);
        $this->getJson("/api/conversions/{$conversion->id}/download")->assertStatus(401);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $conversion = Conversion::factory()->create([
            'user_id' => $this->userWithoutPermissions->id,
        ]);

        $this->actingAs($this->userWithoutPermissions)
            ->getJson('/api/conversions')
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermissions)
            ->postJson('/api/conversions', [
                'operation' => 'pdf_to_docx',
                'file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermissions)
            ->getJson("/api/conversions/{$conversion->id}")
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermissions)
            ->getJson("/api/conversions/{$conversion->id}/download")
            ->assertStatus(403);
    }

    public function test_operations_endpoint_returns_allowed_operations(): void
    {
        $response = $this->actingAs($this->userWithPermissions)
            ->getJson('/api/conversions/operations')
            ->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'operation',
                    'label',
                    'input_format',
                    'output_format',
                    'allowed_extensions',
                    'allowed_mime_types',
                    'max_file_size_kb',
                ],
            ],
        ]);

        $response->assertJsonFragment(['operation' => 'pdf_to_docx']);
    }

    public function test_invalid_file_type_or_oversize_file_returns_422(): void
    {
        Storage::fake('local');

        // Invalid operation
        $this->actingAs($this->userWithPermissions)
            ->postJson('/api/conversions', [
                'operation' => 'invalid_op',
                'file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['operation']);

        // Invalid extension
        $this->actingAs($this->userWithPermissions)
            ->postJson('/api/conversions', [
                'operation' => 'pdf_to_docx',
                'file' => UploadedFile::fake()->create('test.txt', 100, 'text/plain'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // Oversized file (> 10MB = 10240KB)
        $this->actingAs($this->userWithPermissions)
            ->postJson('/api/conversions', [
                'operation' => 'pdf_to_docx',
                'file' => UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_valid_upload_returns_202_and_dispatches_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->userWithPermissions)
            ->postJson('/api/conversions', [
                'operation' => 'pdf_to_docx',
                'file' => $file,
            ])
            ->assertStatus(202);

        $conversionId = $response->json('data.id');
        $this->assertNotNull($conversionId);

        $this->assertDatabaseHas('conversions', [
            'id' => $conversionId,
            'user_id' => $this->userWithPermissions->id,
            'operation' => 'pdf_to_docx',
            'original_name' => 'document.pdf',
            'status' => 'pending',
            'progress' => 0,
        ]);

        $conversion = Conversion::find($conversionId);
        $this->assertNotNull($conversion->input_path);
        // Stored filename must be randomized, not the user's original name
        $this->assertStringNotContainsString('document.pdf', $conversion->input_path);
        Storage::disk('local')->assertExists($conversion->input_path);

        Queue::assertPushed(ProcessConversionJob::class, function ($job) use ($conversion) {
            return $job->conversion->id === $conversion->id;
        });
    }

    public function test_user_cannot_view_or_download_another_users_conversion(): void
    {
        Storage::fake('local');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        $fakePath = 'conversions/outputs/'.Str::uuid().'.docx';
        Storage::disk('local')->put($fakePath, 'sample docx binary');

        $otherConversion = Conversion::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'completed',
            'output_path' => $fakePath,
        ]);

        // Attempt show
        $this->actingAs($this->userWithPermissions)
            ->getJson("/api/conversions/{$otherConversion->id}")
            ->assertStatus(403);

        // Attempt download
        $this->actingAs($this->userWithPermissions)
            ->getJson("/api/conversions/{$otherConversion->id}/download")
            ->assertStatus(403);
    }

    public function test_webhook_with_invalid_signature_returns_400(): void
    {
        Config::set('services.cloudconvert.webhook_secret', 'correct_secret');

        $payload = json_encode(['event' => 'job.finished']);

        // Missing signature
        $this->call('POST', '/api/webhooks/cloudconvert', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload)
            ->assertStatus(400);

        // Invalid signature
        $this->call('POST', '/api/webhooks/cloudconvert', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CloudConvert-Signature' => 'invalid_signature_hash',
        ], $payload)
            ->assertStatus(400);
    }

    public function test_valid_job_finished_webhook_completes_conversion_and_duplicate_changes_nothing(): void
    {
        Storage::fake('local');
        $secret = 'webhook_secret_key_123';
        Config::set('services.cloudconvert.webhook_secret', $secret);

        $conversion = Conversion::factory()->create([
            'user_id' => $this->userWithPermissions->id,
            'status' => 'processing',
            'progress' => 50,
            'cloudconvert_job_id' => 'job-cc-finished-123',
            'operation' => 'pdf_to_docx',
        ]);

        $exportDownloadUrl = 'https://storage.cloudconvert.com/test-files/result.docx';
        Http::fake([
            $exportDownloadUrl => Http::response('mocked docx content', 200),
        ]);

        $payloadArray = [
            'event' => 'job.finished',
            'job' => [
                'id' => 'job-cc-finished-123',
                'tag' => $conversion->id,
                'status' => 'finished',
                'tasks' => [
                    [
                        'name' => 'export-task',
                        'operation' => 'export/url',
                        'status' => 'finished',
                        'result' => [
                            'files' => [
                                ['url' => $exportDownloadUrl],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $payload = json_encode($payloadArray);
        $signature = hash_hmac('sha256', $payload, $secret);

        // First webhook call
        $response = $this->call('POST', '/api/webhooks/cloudconvert', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CloudConvert-Signature' => $signature,
        ], $payload);

        $response->assertStatus(200);

        $conversion->refresh();
        $this->assertEquals('completed', $conversion->status);
        $this->assertEquals(100, $conversion->progress);
        $this->assertNotNull($conversion->completed_at);
        $this->assertNotNull($conversion->output_path);
        Storage::disk('local')->assertExists($conversion->output_path);

        $completedAt = $conversion->completed_at;
        $outputPath = $conversion->output_path;

        // Duplicate webhook call - should return 200 and change nothing (idempotent)
        $duplicateResponse = $this->call('POST', '/api/webhooks/cloudconvert', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CloudConvert-Signature' => $signature,
        ], $payload);

        $duplicateResponse->assertStatus(200);

        $conversion->refresh();
        $this->assertEquals('completed', $conversion->status);
        $this->assertEquals($completedAt->toISOString(), $conversion->completed_at->toISOString());
        $this->assertEquals($outputPath, $conversion->output_path);
    }

    public function test_job_failed_webhook_marks_conversion_failed(): void
    {
        $secret = 'webhook_secret_key_123';
        Config::set('services.cloudconvert.webhook_secret', $secret);

        $conversion = Conversion::factory()->create([
            'user_id' => $this->userWithPermissions->id,
            'status' => 'processing',
            'progress' => 30,
            'cloudconvert_job_id' => 'job-cc-fail-999',
            'operation' => 'pdf_to_docx',
        ]);

        $payloadArray = [
            'event' => 'job.failed',
            'job' => [
                'id' => 'job-cc-fail-999',
                'tag' => $conversion->id,
                'status' => 'error',
                'tasks' => [],
            ],
        ];

        $payload = json_encode($payloadArray);
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/api/webhooks/cloudconvert', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CloudConvert-Signature' => $signature,
        ], $payload);

        $response->assertStatus(200);

        $conversion->refresh();
        $this->assertEquals('failed', $conversion->status);
        $this->assertEquals(100, $conversion->progress);
        $this->assertNotNull($conversion->error_message);
    }

    public function test_user_can_view_own_conversion_and_download_when_completed(): void
    {
        Storage::fake('local');

        $outputPath = 'conversions/outputs/'.Str::uuid().'.docx';
        Storage::disk('local')->put($outputPath, 'test binary docx content');

        $conversion = Conversion::factory()->create([
            'user_id' => $this->userWithPermissions->id,
            'operation' => 'pdf_to_docx',
            'original_name' => 'report.pdf',
            'status' => 'completed',
            'progress' => 100,
            'output_path' => $outputPath,
        ]);

        // Show endpoint
        $showResponse = $this->actingAs($this->userWithPermissions)
            ->getJson("/api/conversions/{$conversion->id}")
            ->assertStatus(200);

        $showResponse->assertJsonFragment([
            'id' => $conversion->id,
            'status' => 'completed',
            'progress' => 100,
            'original_name' => 'report.pdf',
        ]);

        // Download endpoint
        $downloadResponse = $this->actingAs($this->userWithPermissions)
            ->getJson("/api/conversions/{$conversion->id}/download")
            ->assertStatus(200);

        $downloadResponse->assertHeader(
            'content-disposition',
            'attachment; filename=report.docx'
        );
    }

    public function test_download_returns_400_when_conversion_is_not_completed(): void
    {
        $conversion = Conversion::factory()->create([
            'user_id' => $this->userWithPermissions->id,
            'status' => 'processing',
            'progress' => 50,
            'output_path' => null,
        ]);

        $this->actingAs($this->userWithPermissions)
            ->getJson("/api/conversions/{$conversion->id}/download")
            ->assertStatus(400);
    }

    public function test_dashboard_lists_only_current_users_conversions(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        Conversion::factory()->count(3)->create([
            'user_id' => $this->userWithPermissions->id,
        ]);

        Conversion::factory()->count(2)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->userWithPermissions)
            ->getJson('/api/conversions')
            ->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(3, $data);
        foreach ($data as $item) {
            $this->assertEquals($this->userWithPermissions->id, $item['user_id']);
        }
    }
}
