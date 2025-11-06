<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\PasswordHistory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'status' => UserStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            $password = $user->getAuthPassword();

            if ($password !== null) {
                $user->recordPasswordHistory($password);
            }
        });

        static::updated(function (self $user): void {
            if ($user->wasChanged('password')) {
                $password = $user->getAuthPassword();

                if ($password !== null) {
                    $user->recordPasswordHistory($password);
                }
            }
        });
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'author_id');
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class)->orderByDesc('created_at');
    }

    public function recordPasswordHistory(string $hashedPassword): void
    {
        $historyLimit = (int) config('security.password.reuse_prevent', 0);

        $this->passwordHistories()->create([
            'password' => $hashedPassword,
        ]);

        if ($historyLimit <= 0) {
            return;
        }

        $idsToDelete = $this->passwordHistories()
            ->orderByDesc('created_at')
            ->pluck('id')
            ->slice($historyLimit)
            ->values();

        if ($idsToDelete->isNotEmpty()) {
            PasswordHistory::whereIn('id', $idsToDelete->all())->delete();
        }
    }
}
