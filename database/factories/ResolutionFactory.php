<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResolutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'meeting_id' => Meeting::factory(),
            'resolution_file_path' => null,
            'resolution_number' => null,
            'generated_pdf_path' => null,
            'generated_docx_path' => null,
            'signed_file_path' => null,
            'physical_signatures_pdf_path' => null,
            'dswd_approval_file_path' => null,
            'version' => 1,
            'signature_verified_at' => null,
            'workflow_status' => 'draft',
            'resolution_number_assigned_at' => null,
            'status' => 'draft',
            'approval_deadline' => null,
            'is_approval_locked' => false,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
