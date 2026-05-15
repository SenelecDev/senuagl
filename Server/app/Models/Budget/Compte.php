<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compte extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'numero',
        'intitule',
        'parent_id',
    ];

    /**
     * @return BelongsTo<Compte, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'parent_id');
    }

    /**
     * @return HasMany<Compte, $this>
     */
    public function enfants(): HasMany
    {
        return $this->hasMany(Compte::class, 'parent_id');
    }

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
