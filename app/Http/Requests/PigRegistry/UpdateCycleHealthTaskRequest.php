<?php

namespace App\Http\Requests\PigRegistry;

use App\Models\CycleHealthTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCycleHealthTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('president') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['complete_all', 'partial', 'reschedule', 'skip', 'not_applicable'])],
            'completed_count' => ['nullable', 'integer', 'min:0'],
            'actual_date' => ['nullable', 'date'],
            'follow_up_date' => ['nullable', 'date'],
            'planned_start_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $action = (string) $this->input('action');
            $task = $this->route('healthTask');

            if (! $task instanceof CycleHealthTask) {
                return;
            }

            if ($action === 'partial' && ! $this->filled('completed_count')) {
                $validator->errors()->add('completed_count', 'Completed count is required for partial completion.');
            }

            if ($action === 'reschedule' && ! $this->filled('planned_start_date') && ! $this->filled('follow_up_date')) {
                $validator->errors()->add('planned_start_date', 'Provide a new planned date or follow-up date for rescheduling.');
            }

            if (in_array($action, ['skip', 'not_applicable'], true) && ! (bool) $task->is_optional) {
                $validator->errors()->add('action', 'Only optional tasks may be skipped or marked not applicable.');
            }

            $cycleCurrentCount = (int) ($task->cycle()->value('current_count') ?? 0);

            if ($this->filled('completed_count') && (int) $this->input('completed_count') > $cycleCurrentCount) {
                $validator->errors()->add('completed_count', 'Completed count cannot be greater than current cycle count.');
            } elseif ($this->filled('completed_count') && (int) $this->input('completed_count') > (int) $task->target_count) {
                $validator->errors()->add('completed_count', 'Completed count cannot be greater than target count.');
            }

            if (
                $this->filled('actual_date')
                && $this->filled('follow_up_date')
                && (string) $this->input('follow_up_date') < (string) $this->input('actual_date')
            ) {
                $validator->errors()->add('follow_up_date', 'Follow-up date cannot be earlier than actual treatment date.');
            }
        });
    }
}
