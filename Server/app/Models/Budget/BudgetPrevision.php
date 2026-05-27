<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetPrevision extends Model
{
    protected $table = 'budget_previsions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'compte_id',
        'montant_prevu',
        'annee',
        'mois',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'montant_prevu' => 'decimal:2',
            'annee' => 'integer',
            'mois' => 'integer',
        ];
    }


    /**
     * @return BelongsTo<Compte, $this>
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }
}
