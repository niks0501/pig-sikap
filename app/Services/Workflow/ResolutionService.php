<?php

namespace App\Services\Workflow;

use App\Models\Meeting;
use App\Models\Resolution;
use App\Models\ResolutionLineItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Creates and manages resolutions, including budget line-items.
 */
class ResolutionService
{
    /**
     * Create a resolution from a meeting with optional line-items.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Resolution
    {
        return DB::transaction(function () use ($data, $user) {
            $filePath = null;

            if (isset($data['resolution_file']) && $data['resolution_file'] instanceof UploadedFile) {
                $filePath = $data['resolution_file']->store('resolutions', 'public');
            }

            $resolution = Resolution::create([
                'meeting_id' => $data['meeting_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'resolution_file_path' => $filePath,
                'status' => 'draft',
                'approval_deadline' => $data['approval_deadline'] ?? null,
                'created_by' => $user->id,
            ]);

            // Create budget line-items
            if (! empty($data['line_items'])) {
                foreach ($data['line_items'] as $index => $item) {
                    $total = round(($item['quantity'] ?? 1) * ($item['unit_cost'] ?? 0), 2);

                    ResolutionLineItem::create([
                        'resolution_id' => $resolution->id,
                        'category' => $item['category'],
                        'description' => $item['description'],
                        'quantity' => $item['quantity'] ?? 1,
                        'unit' => $item['unit'] ?? 'pc',
                        'unit_cost' => $item['unit_cost'] ?? 0,
                        'total' => $total,
                        'sort_order' => $index,
                    ]);
                }
            }

            return tap($resolution->load('lineItems', 'meeting'), function ($resolution) {
                event(new \App\Events\Workflow\ResolutionCreated($resolution));
            });
        });
    }

    /**
     * Transition resolution status.
     */
    public function changeStatus(Resolution $resolution, string $newStatus, User $user): Resolution
    {
        $resolution->update([
            'status' => $newStatus,
            'updated_by' => $user->id,
        ]);

        return $resolution->fresh();
    }

    /**
     * Auto-create a resolution from a meeting, pre-filling fields.
     */
    public function createFromMeeting(Meeting $meeting, User $user): Resolution
    {
        return $this->create([
            'meeting_id' => $meeting->id,
            'title' => "Resolution from: {$meeting->title}",
            'description' => $meeting->agenda,
        ], $user);
    }
}
