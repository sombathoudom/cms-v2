<?php

namespace App\Http\Requests\Api;

use App\Enums\UserStatus;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => array_merge(['required', 'confirmed'], PasswordRules::forUser()),
            'password_confirmation' => ['required', 'string'],
            'status' => ['required', Rule::in(array_map(fn (UserStatus $status) => $status->value, UserStatus::cases()))],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
