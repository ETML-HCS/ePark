<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function favoriteSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'favorite_site_id');
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'favorite_site_id',
        'onboarded',
        'secret_group_codes',
        'express_token',
        'auto_pay_enabled',
        'quick_vehicle_plate',
        'twint_phone',
        'iban',
        'payment_instructions',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'express_token',
        'secret_group_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'onboarded' => 'boolean',
        'auto_pay_enabled' => 'boolean',
        'secret_group_codes' => 'encrypted:array',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->express_token)) {
                $user->express_token = 'epk_'.bin2hex(random_bytes(16));
            }
        });
    }

    /**
     * Retourne le token express ou en génère un si absent.
     */
    public function getOrGenerateExpressToken(): string
    {
        if (empty($this->express_token)) {
            $token = 'epk_'.bin2hex(random_bytes(16));
            $this->express_token = $token;
            $this->save();

            return $token;
        }

        return $this->express_token;
    }

    /**
     * Régénère un nouveau token express unique.
     */
    public function regenerateExpressToken(): string
    {
        $token = 'epk_'.bin2hex(random_bytes(16));
        $this->express_token = $token;
        $this->save();

        return $token;
    }

    /**
     * Trouve un utilisateur via son token express.
     */
    public static function findByExpressToken(?string $token): ?self
    {
        $clean = trim((string) $token);
        if ($clean === '') {
            return null;
        }

        return static::where('express_token', $clean)->first();
    }

    /**
     * @return array<int, array{name: string, code: string}>
     */
    public function secretGroupEntries(): array
    {
        $rawEntries = is_array($this->secret_group_codes) ? $this->secret_group_codes : [];

        $entries = collect($rawEntries)
            ->map(function ($entry) {
                if (is_string($entry)) {
                    $code = trim($entry);
                    if ($code === '') {
                        return null;
                    }

                    return [
                        'name' => '',
                        'code' => $code,
                    ];
                }

                if (is_array($entry)) {
                    $code = trim((string) ($entry['code'] ?? ''));
                    $name = trim((string) ($entry['name'] ?? ''));

                    if ($code === '') {
                        return null;
                    }

                    return [
                        'name' => $name,
                        'code' => $code,
                    ];
                }

                return null;
            })
            ->filter()
            ->unique(fn (array $entry) => mb_strtolower($entry['code']))
            ->values();

        return $entries->all();
    }

    /**
     * @return array<int, string>
     */
    public function normalizedSecretGroupCodes(): array
    {
        return collect($this->secretGroupEntries())
            ->map(fn (array $entry) => $entry['code'])
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Relations supplémentaires
    // -------------------------------------------------------------------------

    /**
     * Feedbacks laissés par l'utilisateur.
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    // -------------------------------------------------------------------------
    // Méthodes utilitaires pour les rôles
    // -------------------------------------------------------------------------

    /**
     * Retourne l'adresse e-mail uniquement si elle a été vérifiée.
     */
    public function verifiedEmail(): ?string
    {
        return $this->hasVerifiedEmail() ? $this->email : null;
    }

    /**
     * Vérifie si l'utilisateur est admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifie si l'utilisateur est propriétaire (ou hybride).
     */
    public function isOwner(): bool
    {
        return in_array($this->role, ['proprietaire', 'les deux'], true);
    }

    /**
     * Vérifie si l'utilisateur est locataire (ou hybride).
     */
    public function isTenant(): bool
    {
        return in_array($this->role, ['locataire', 'les deux'], true);
    }

    /**
     * Vérifie si l'utilisateur peut réserver des places.
     */
    public function canReserve(): bool
    {
        return $this->isTenant() || $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut proposer des places.
     */
    public function canOffer(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }
}
