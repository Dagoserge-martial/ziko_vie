<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'utilisateurs';

    protected static function boot()
    {
        parent::boot();

        // Hasher automatiquement le mot de passe lors de la création
        static::creating(function ($user) {
            // Vérifier si 'password' est dans les attributs (via fillable)
            if (array_key_exists('password', $user->attributes) && !empty($user->attributes['password'])) {
                $user->attributes['mot_de_passe'] = Hash::make($user->attributes['password']);
                unset($user->attributes['password']);
            }
        });

        // Hasher automatiquement le mot de passe lors de la mise à jour
        static::updating(function ($user) {
            // Vérifier si 'password' est dans les attributs modifiés
            if (array_key_exists('password', $user->attributes) && !empty($user->attributes['password'])) {
                $user->attributes['mot_de_passe'] = Hash::make($user->attributes['password']);
                unset($user->attributes['password']);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password', // Utilisé pour déclencher le mutator setPasswordAttribute
        'mot_de_passe', // Pour assignation directe si nécessaire
        'role_id',
        'dernier_login',
        'est_bloque',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'mot_de_passe',
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
            'dernier_login' => 'datetime',
            'est_bloque' => 'boolean',
        ];
    }

    /**
     * Get the password for authentication.
     * Laravel utilise cette méthode pour obtenir le mot de passe lors de l'authentification
     */
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    /**
     * Get the password attribute (accessor pour compatibilité).
     * Laravel cherche automatiquement un attribut 'password', 
     * donc on crée un accessor pour mapper 'password' vers 'mot_de_passe'
     */
    public function getPasswordAttribute()
    {
        return $this->mot_de_passe;
    }

    /**
     * Set the password attribute.
     * Quand on assigne 'password', on le hash et on le stocke dans 'mot_de_passe'
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value) && !Hash::needsRehash($value)) {
            // Si c'est déjà hashé, le stocker directement
            $this->attributes['mot_de_passe'] = $value;
        } elseif (!empty($value)) {
            // Sinon, hasher le mot de passe
            $this->attributes['mot_de_passe'] = Hash::make($value);
        }
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function membre(): HasOne
    {
        return $this->hasOne(Membre::class, 'utilisateur_id');
    }

    // Accessor pour compatibilité avec les vues existantes (Breeze)
    public function getNameAttribute()
    {
        // Si l'utilisateur a un membre associé, utiliser son nom complet
        if ($this->membre) {
            return $this->membre->prenom . ' ' . $this->membre->nom;
        }
        // Sinon, utiliser l'email comme nom
        return $this->email;
    }

    /**
     * Vérifier si l'utilisateur est administrateur
     */
    public function isAdmin(): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->slug === 'admin' || 
               in_array('admin', $this->role->permissions ?? []);
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true; // Les admins ont toutes les permissions
        }

        if (!$this->role || !$this->role->actif) {
            return false;
        }

        $permissions = $this->role->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $roleSlug): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->slug === $roleSlug;
    }
}
