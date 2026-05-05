<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $user = \App\Models\User::find($value);
                if ($user && ! $user->is_active) {
                    $fail('One or more selected members are not active.');
                }
            }],
        ];
    }
}