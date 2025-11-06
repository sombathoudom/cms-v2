<?php

namespace App\Support;

use App\Models\User;
use App\Rules\NotInRecentPassword;
use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    /**
     * @return array<int, mixed>
     */
    public static function forUser(?User $user = null): array
    {
        $rules = [Password::defaults()];

        if ($user !== null) {
            $rules[] = new NotInRecentPassword($user);
        }

        return $rules;
    }
}
