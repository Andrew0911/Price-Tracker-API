<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verification_token',
        'email_verification_token_expires_at',
        'private_account',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_token_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'private_account' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (User $user): void {
            if (!$user->wasChanged('is_admin') || !$user->is_admin) {
                return;
            }

            Friendship::query()
                ->where('user_id', $user->id)
                ->orWhere('friend_id', $user->id)
                ->delete();

            Message::query()
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhere('receiver_id', $user->id);
                })
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function friends(): HasMany
    {
        return $this->hasMany(\App\Models\Friendship::class, 'user_id');
    }

    public function receivedRequests(): HasMany
    {
        return $this->hasMany(\App\Models\Friendship::class, 'friend_id');
    }
}
