<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compte extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'numero',
        'intitule',
    ];

    /**
     * @return HasMany<BudgetPrevision, $this>
     */
    public function budgetPrevisions(): HasMany
    {
        return $this->hasMany(BudgetPrevision::class, 'compte_id');
    }

    /**
     * @return HasMany<Realisation, $this>
     */
    public function realisations(): HasMany
    {
        return $this->hasMany(Realisation::class, 'compte_id');
    }
}
