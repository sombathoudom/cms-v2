<?php

namespace App\Http\Requests\Api;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->getKey() : null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => array_merge(['sometimes', 'nullable', 'confirmed'], PasswordRules::forUser($user instanceof User ? $user : null)),
            'password_confirmation' => ['required_with:password', 'string'],
            'status' => ['sometimes', Rule::in(array_map(fn (UserStatus $status) => $status->value, UserStatus::cases()))],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
