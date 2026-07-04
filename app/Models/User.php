<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * User — akun server Lentera. UUID PK. Login via password_hash (argon2id).
 * Kolom kripto (kdf_salt/totp_secret_enc) tak pernah dipakai server untuk
 * membaca jurnal E2E.
 *
 * Mengimplementasikan JWTSubject (auth API mobile via tymon/jwt-auth).
 */
class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    /** Subject JWT = UUID user. */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /** Klaim kustom pada token JWT. */
    public function getJWTCustomClaims(): array
    {
        return ['role' => $this->role];
    }

    // Kolom created_at/updated_at kita kelola manual (timestampTz) — biarkan default true.
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'handle',
        'email',
        'password_hash',
        'kdf_salt',
        'totp_secret_enc',
        'totp_enabled',
        'role',
        'status',
        'sync_on',
        'reminder_on',
        'reminder_at',
        'accent',
        'theme',
    ];

    protected $hidden = [
        'password_hash',
        'kdf_salt',
        'totp_secret_enc',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'totp_enabled' => 'boolean',
            'sync_on' => 'boolean',
            'reminder_on' => 'boolean',
            'kdf_salt' => \App\Casts\Bytea::class,
            'totp_secret_enc' => \App\Casts\Bytea::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Sanctum & auth memakai kolom password bernama non-standar.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // --- Relasi ---

    public function identities(): HasMany
    {
        return $this->hasMany(AuthIdentity::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function vaultBackup()
    {
        return $this->hasOne(VaultBackup::class);
    }

    public function circleMemberships(): HasMany
    {
        return $this->hasMany(CircleMember::class);
    }
}
