<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepenseMedicale extends Model
{
    use HasFactory;

    protected $table = 'depenses_medicales';

    protected $fillable = [
        'membre_id',
        'categorie_depense_id',
        'description',
        'montant',
        'date_depense',
        'nom_prestataire',
        'personne_deleguee',
        'transport_pers_deleguee',
        'montant_total',
        'utilisateur_id',
    ];

    protected $casts = [
        'date_depense' => 'date',
        'montant' => 'decimal:2',
        'montant_total' => 'decimal:2',
    ];

    // Calculer automatiquement montant_total avant la sauvegarde
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($depense) {
            $montant = (float) ($depense->montant ?? 0);
            $transport = (float) ($depense->transport_pers_deleguee ?? 0);
            $depense->montant_total = $montant + $transport;
        });
    }

    // Accessors et mutators pour compatibilité avec le code existant
    public function getNomDelegueAttribute()
    {
        return $this->personne_deleguee;
    }

    public function setNomDelegueAttribute($value)
    {
        $this->attributes['personne_deleguee'] = $value;
    }

    public function getMontantTransportAttribute()
    {
        return $this->transport_pers_deleguee ? (float) $this->transport_pers_deleguee : null;
    }

    public function setMontantTransportAttribute($value)
    {
        $this->attributes['transport_pers_deleguee'] = $value ? (string) $value : null;
    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'membre_id');
    }

    public function categorieDepense(): BelongsTo
    {
        return $this->belongsTo(CategorieDepense::class, 'categorie_depense_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class, 'depense_medicale_id');
    }

    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
