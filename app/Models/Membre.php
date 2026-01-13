<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membre extends Model
{
    use HasFactory;

    protected $table = 'membres';

    protected $fillable = [
        'utilisateur_id',
        'nom',
        'prenom',
        'telephone',
        'localite_id',
        'photo_url',
        'adresse',
        'date_adhesion',
        'statut', // 0 = actif, 1 = inactif
    ];

    protected $casts = [
        'date_adhesion' => 'date',
        'statut' => 'integer',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function localite(): BelongsTo
    {
        return $this->belongsTo(Localite::class, 'localite_id');
    }

    public function cotisations(): HasMany
    {
        return $this->hasMany(Cotisation::class, 'membre_id');
    }

    public function depensesMedicales(): HasMany
    {
        return $this->hasMany(DepenseMedicale::class, 'membre_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    /**
     * Vérifier si le membre est actif
     */
    public function isActif(): bool
    {
        return $this->statut === 0;
    }

    /**
     * Accessor pour obtenir le statut en texte
     */
    public function getStatutTextAttribute(): string
    {
        return $this->statut === 0 ? 'Actif' : 'Inactif';
    }

    public function getTotalCotisationsAttribute(): float
    {
        return $this->cotisations()->sum('montant');
    }

    public function getTotalDepensesAttribute(): float
    {
        return $this->depensesMedicales()->sum('montant');
    }

    public function isUpToDate(): bool
    {
        // Récupérer la dernière cotisation avec son statut
        $lastCotisation = $this->cotisations()
            ->with('statutCotisation')
            ->latest('date_paiement')
            ->first();
        
        if (!$lastCotisation || !$lastCotisation->statutCotisation) {
            return false;
        }

        // Vérifier si le statut indique que la cotisation est payée
        // On cherche des noms de statut qui indiquent un paiement (Payé, Payée, Validé, etc.)
        $statutNom = strtolower($lastCotisation->statutCotisation->nom ?? '');
        $statutsPayes = ['payé', 'payée', 'paye', 'validé', 'validée', 'valide', 'acquitté', 'acquittée'];
        
        if (!in_array($statutNom, $statutsPayes)) {
            return false;
        }

        // Vérifier si la dernière cotisation payée date de moins de 30 jours
        // Vérifier que date_paiement n'est pas null avant d'appeler diffInDays
        if (!$lastCotisation->date_paiement) {
            return false;
        }
        
        return $lastCotisation->date_paiement->diffInDays(now()) <= 30;
    }
}
