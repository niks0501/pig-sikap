<?php

namespace App\Services\PigRegistry;

use App\Models\Pig;
use App\Models\PigBatch;

class GeneratePigProfilesService
{
    public function handle(PigBatch $batch, int $count, int $createdBy): void
    {
        if ($count < 1) {
            return;
        }

        $lastPigNo = (int) $batch->pigs()->max('pig_no');

        for ($offset = 1; $offset <= $count; $offset++) {
            Pig::create([
                'batch_id' => $batch->id,
                'pig_no' => $lastPigNo + $offset,
                'status' => 'Active',
                'created_by' => $createdBy,
            ]);
        }
    }
}
