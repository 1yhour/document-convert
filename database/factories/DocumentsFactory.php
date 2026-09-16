<?php

namespace Database\Factories;

use App\Models\Documents;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Documents>
 */
class DocumentsFactory extends Factory
{
    protected $model = Documents::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'docx', 'xlsx', 'png', 'jpg']);
        $mimeMap = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
        ];

        return [
            'user_id' => User::factory(),
            'original_name' => fake()->word() . '.' . $extension,
            'stored_path' => 'documents/' . Str::uuid() . '.' . $extension,
            'disk' => 'local',
            'mime_type' => $mimeMap[$extension] ?? 'application/octet-stream',
            'extention' => $extension,
            'size_bytes' => fake()->numberBetween(1024, 10485760),
            'checksum' => hash('sha256', fake()->text()),
            'status' => fake()->randomElement(['uploaded', 'archived']),
        ];
    }
}
