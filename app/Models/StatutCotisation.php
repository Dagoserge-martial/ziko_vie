<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatutCotisation extends Model
{
    use HasFactory;

    protected $table = 'statuts_cotisation';

    protected $fillable = [
        'libelle',
        'description',
    ];

    public function cotisations(): HasMany
    {
        return $this->hasMany(Cotisation::class, 'statut_cotisation_id');
    }

    // Accessor pour compatibilité avec les vues qui utilisent 'nom'
    public function getNomAttribute()
    {
        return $this->libelle;
    }
}
