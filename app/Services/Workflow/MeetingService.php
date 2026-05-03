<?php

namespace App\Services\Workflow;

use App\Models\Meeting;
use App\Models\MeetingSignatory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Handles meeting creation and attendee assignment.
 */
class MeetingService
{
    /**
     * Create a meeting with optional file upload and attendees.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Meeting
    {
        return DB::transaction(function () use ($data, $user) {
            $filePath = null;

            if (isset($data['minutes_file']) && $data['minutes_file'] instanceof UploadedFile) {
                $filePath = $data['minutes_file']->store('meetings', 'public');
            }

            $meeting = Meeting::create([
                'title' => $data['title'],
                'date' => $data['date'],
                'location' => $data['location'] ?? null,
                'agenda' => $data['agenda'] ?? null,
                'minutes_summary' => $data['minutes_summary'] ?? null,
                'minutes_file_path' => $filePath,
                'created_by' => $user->id,
                'status' => $data['status'] ?? 'draft',
            ]);

            // Attach attendees if provided
            if (! empty($data['attendees'])) {
                foreach ($data['attendees'] as $attendee) {
                    MeetingSignatory::create([
                        'meeting_id' => $meeting->id,
                        'user_id' => $attendee['user_id'],
                        'attendance_status' => $attendee['attendance_status'] ?? 'present',
                    ]);
                }
            }

            return $meeting->load('signatories.user');
        });
    }

    /**
     * Update an existing meeting.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Meeting $meeting, array $data, User $user): Meeting
    {
        return DB::transaction(function () use ($meeting, $data, $user) {
            $updateData = [
                'title' => $data['title'] ?? $meeting->title,
                'date' => $data['date'] ?? $meeting->date,
                'location' => $data['location'] ?? $meeting->location,
                'agenda' => $data['agenda'] ?? $meeting->agenda,
                'minutes_summary' => $data['minutes_summary'] ?? $meeting->minutes_summary,
                'updated_by' => $user->id,
                'status' => $data['status'] ?? $meeting->status,
            ];

            if (isset($data['minutes_file']) && $data['minutes_file'] instanceof UploadedFile) {
                $updateData['minutes_file_path'] = $data['minutes_file']->store('meetings', 'public');
            }

            $meeting->update($updateData);

            // Sync attendees if provided
            if (isset($data['attendees'])) {
                $meeting->signatories()->delete();

                foreach ($data['attendees'] as $attendee) {
                    MeetingSignatory::create([
                        'meeting_id' => $meeting->id,
                        'user_id' => $attendee['user_id'],
                        'attendance_status' => $attendee['attendance_status'] ?? 'present',
                    ]);
                }
            }

            return $meeting->fresh(['signatories.user']);
        });
    }
}
