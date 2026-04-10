<?php

namespace App\Services\PigRegistry;

use App\Models\Pig;
use App\Models\PigCycle;

class GeneratePigProfilesService
{
    public function handle(PigCycle $cycle, int $count, int $createdBy): void
    {
        if ($count < 1) {
            return;
        }

        $lastPigNo = (int) $cycle->pigs()->max('pig_no');

        for ($offset = 1; $offset <= $count; $offset++) {
            Pig::create([
                'batch_id' => $cycle->id,
                'pig_no' => $lastPigNo + $offset,
                'status' => 'Active',
                'created_by' => $createdBy,
            ]);
        }
    }
}
