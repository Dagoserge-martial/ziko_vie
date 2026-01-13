<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Localite extends Model
{
    use HasFactory;

    protected $table = 'localite';

    protected $fillable = [
        'libelle',
    ];

    public function membres(): HasMany
    {
        return $this->hasMany(Membre::class, 'localite_id');
    }
}

