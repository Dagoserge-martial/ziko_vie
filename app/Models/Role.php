<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nom',
        'slug',
        'description',
        'permissions',
        'actif',
    ];

    protected $casts = [
        'permissions' => 'array',
        'actif' => 'boolean',
    ];

    // Relation many-to-many avec les utilisateurs
    public function utilisateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_utilisateur', 'role_id', 'utilisateur_id')
            ->withTimestamps();
    }

    // Relation HasMany pour compatibilité rétroactive (ancien système role_id)
    public function utilisateursLegacy(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
