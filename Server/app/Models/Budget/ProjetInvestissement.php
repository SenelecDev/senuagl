<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;

class ProjetInvestissement extends Model
{
    protected $table = 'projet_investissements';

    protected $fillable = [
        'code_projet',
        'libelle',
        'bailleur',
        'cr',
        'montant_marche',
        'cout_projet',
        'fp_annee',
        'fe_annee',
        'annee',
    ];

    protected $casts = [
        'montant_marche' => 'decimal:2',
        'cout_projet' => 'decimal:2',
        'fp_annee' => 'decimal:2',
        'fe_annee' => 'decimal:2',
        'annee' => 'integer',
    ];
}
