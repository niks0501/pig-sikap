<?php

namespace App\Services\Workflow;

use App\Models\DswdSubmission;
use App\Models\Resolution;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Manages DSWD submission status and file uploads.
 */
class DswdService
{
    /**
     * Create or update a DSWD submission for a resolution.
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(Resolution $resolution, array $data, User $user): DswdSubmission
    {
        $filePath = null;

        if (isset($data['submission_file']) && $data['submission_file'] instanceof UploadedFile) {
            $filePath = $data['submission_file']->store('dswd_submissions', 'public');
        }

        $updateData = [
            'status' => $data['status'],
            'submission_file_path' => $filePath ?? DswdSubmission::where('resolution_id', $resolution->id)->value('submission_file_path'),
            'submitted_at' => in_array($data['status'], ['submitted', 'approved']) ? now() : null,
            'notes' => $data['notes'] ?? null,
            'submitted_by' => $user->id,
        ];

        // Record the DSWD approval date when status transitions to approved
        if ($data['status'] === 'approved') {
            $updateData['dswd_approval_date'] = now();
        }

        $submission = DswdSubmission::updateOrCreate(
            ['resolution_id' => $resolution->id],
            $updateData
        );

        // Auto-advance resolution status if DSWD approved
        if ($data['status'] === 'approved' && $resolution->status === 'approved') {
            $resolution->update(['status' => 'dswd_submitted']);
        }

        // If DSWD submitted (not yet approved), also update resolution
        if ($data['status'] === 'submitted' && $resolution->status === 'approved') {
            $resolution->update(['status' => 'dswd_submitted']);
        }

        event(new \App\Events\Workflow\DswdSubmitted($resolution, $submission));

        return $submission;
    }
}
