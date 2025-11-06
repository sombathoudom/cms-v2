<?php

namespace App\Http\Requests\Api;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255', 'exists:roles,name'],
            'status' => ['nullable', Rule::in(array_map(fn (UserStatus $status) => $status->value, UserStatus::cases()))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'trashed' => ['nullable', Rule::in(['with', 'only'])],
        ];
    }
}
