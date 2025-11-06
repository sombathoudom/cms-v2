<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class NotInRecentPassword implements ValidationRule
{
    public function __construct(private readonly ?User $user)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->user === null) {
            return;
        }

        $historyLimit = (int) config('security.password.reuse_prevent', 0);

        if ($historyLimit <= 0) {
            return;
        }

        $recentPasswords = $this->user->passwordHistories()
            ->orderByDesc('created_at')
            ->take($historyLimit)
            ->get(['password']);

        foreach ($recentPasswords as $history) {
            if (Hash::check((string) $value, $history->password)) {
                $fail(__('auth.password_reuse'));

                return;
            }
        }
    }
}
