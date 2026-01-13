<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAttachment extends Model
{
    use HasFactory;

    protected $table = 'expense_attachments';

    protected $fillable = [
        'depense_medicale_id',
        'chemin_fichier',
        'nom_fichier',
        'type_mime',
        'taille_fichier',
        'description',
    ];

    public function depenseMedicale(): BelongsTo
    {
        return $this->belongsTo(DepenseMedicale::class, 'depense_medicale_id');
    }
}
