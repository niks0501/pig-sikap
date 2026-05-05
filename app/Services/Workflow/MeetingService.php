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
     * Auto-fills structured agenda from meeting type default template
     * if agenda_json is not explicitly provided.
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

            $meetingType = $data['meeting_type'] ?? 'pig_production';

            // Auto-fill agenda_json from default template if not provided
            // Accept both JSON string (from API) and array
            $agendaJson = null;

            if (array_key_exists('agenda_json', $data) && $data['agenda_json'] !== null) {
                // Decode JSON string to array if needed
                $raw = $data['agenda_json'];

                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);

                    if (is_array($decoded)) {
                        $agendaJson = $decoded;
                    }
                } elseif (is_array($raw)) {
                    $agendaJson = $raw;
                }
            }

            $agendaJson = $agendaJson ?? (Meeting::DEFAULT_AGENDA[$meetingType] ?? Meeting::DEFAULT_AGENDA['general']);

            $meeting = Meeting::create([
                'title' => $data['title'],
                'date' => $data['date'],
                'location' => $data['location'] ?? null,
                'agenda' => $data['agenda'] ?? null,
                'agenda_json' => $agendaJson,
                'minutes_summary' => $data['minutes_summary'] ?? null,
                'minutes_file_path' => $filePath,
                'meeting_type' => $meetingType,
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
                'meeting_type' => $data['meeting_type'] ?? $meeting->meeting_type,
            ];

            // Update structured agenda if provided
            if (array_key_exists('agenda_json', $data)) {
                $updateData['agenda_json'] = $data['agenda_json'];
            }

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
