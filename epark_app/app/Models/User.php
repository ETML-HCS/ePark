<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function favoriteSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'favorite_site_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
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
    ];

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
