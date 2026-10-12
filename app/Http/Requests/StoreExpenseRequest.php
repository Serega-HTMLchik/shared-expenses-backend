<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['payer_id' => ['required', 'integer', 'exists:users,id'], 'amount_cents' => ['required', 'integer', 'min:1'], 'split_type' => ['required', Rule::in(['equal', 'shares', 'exact'])], 'shares' => ['required', 'array', 'min:1'], 'shares.*.user_id' => ['required', 'integer', 'distinct', 'exists:users,id'], 'shares.*.units' => ['nullable', 'integer', 'min:1'], 'shares.*.amount_cents' => ['nullable', 'integer', 'min:0'], 'category' => ['nullable', 'string', 'max:255'], 'spent_on' => ['required', 'date'], 'comment' => ['nullable', 'string'], 'receipt_url' => ['nullable', 'url', 'max:2048']];
    }
}
