<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['nullable', 'email', 'max:255'], 'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30']];
    }
}
