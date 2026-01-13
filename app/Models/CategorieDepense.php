<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieDepense extends Model
{
    use HasFactory;

    protected $table = 'categories_depenses';

    protected $fillable = [
        'libelle',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function depensesMedicales(): HasMany
    {
        return $this->hasMany(DepenseMedicale::class, 'categorie_depense_id');
    }

    // Accessor pour compatibilité avec les vues qui utilisent 'nom'
    public function getNomAttribute()
    {
        return $this->libelle;
    }
}
