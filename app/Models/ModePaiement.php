<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModePaiement extends Model
{
    use HasFactory;

    protected $table = 'modes_paiement';

    protected $fillable = [
        'libelle',
        'actif',
    ];

    protected $casts = [
        'actif' => 'integer', // 1 = oui, 0 = non
    ];

    public function cotisations(): HasMany
    {
        return $this->hasMany(Cotisation::class, 'mode_paiement_id');
    }

    // Accessor pour compatibilité avec les vues qui utilisent 'nom'
    public function getNomAttribute()
    {
        return $this->libelle;
    }
}
