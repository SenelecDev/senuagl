<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Realisation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'compte_id',
        'montant_realise',
        'date_realisation',
        'observation',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'montant_realise' => 'decimal:2',
            'date_realisation' => 'date',
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
