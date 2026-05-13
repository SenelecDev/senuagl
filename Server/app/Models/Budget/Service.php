<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'intitule',
    ];

    /**
     * @return HasMany<BudgetPrevision, $this>
     */
    public function budgetPrevisions(): HasMany
    {
        return $this->hasMany(BudgetPrevision::class, 'service_id');
    }

    /**
     * @return HasMany<Realisation, $this>
     */
    public function realisations(): HasMany
    {
        return $this->hasMany(Realisation::class, 'service_id');
    }
}
