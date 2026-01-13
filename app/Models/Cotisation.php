<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotisation extends Model
{
    use HasFactory;

    protected $table = 'cotisations';

    protected $fillable = [
        'membre_id',
        'montant',
        'annee',
        'mois',
        'date_paiement',
        'mode_paiement_id',
        'statut_cotisation_id',
        'reference',
        'notes',
        'enregistre_par',
    ];

    protected $casts = [
        'annee' => 'string',
        'mois' => 'string',
        'montant' => 'decimal:2',
    ];

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'membre_id');
    }

    public function modePaiement(): BelongsTo
    {
        return $this->belongsTo(ModePaiement::class, 'mode_paiement_id');
    }

    public function statutCotisation(): BelongsTo
    {
        return $this->belongsTo(StatutCotisation::class, 'statut_cotisation_id');
    }

    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }
}
