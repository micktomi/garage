<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    /**
     * Matches the column default. Without it a model built in memory (a
     * factory, a seeder, a form object) would report a null role until it
     * was re-read, and every capability check would have to guard for that.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::Owner->value,
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isOwner(): bool
    {
        return $this->validRole() === UserRole::Owner;
    }

    /**
     * The stored role as a known UserRole, or null for anything else (typo,
     * legacy value, manual SQL). Read from the raw column so an unknown value
     * never throws from the enum cast.
     */
    public function validRole(): ?UserRole
    {
        $raw = $this->getAttributes()['role'] ?? null;

        return $raw instanceof UserRole ? $raw : UserRole::tryFrom((string) $raw);
    }

    public function hasValidRole(): bool
    {
        return $this->validRole() !== null;
    }

    /**
     * Both roles use the panel — the counter staff live in it all day. What
     * changes per role is which actions inside it are authorized, which is
     * the policies' job. An account without a known role gets nothing
     * (the same check guards /workshop: EnsureUserHasValidRole).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasValidRole();
    }
}
