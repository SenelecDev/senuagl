<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;

class Engagement extends Model
{
    protected $fillable = [
        'compte_id',
        'montant_engage',
        'date_engagement',
        'observation'
    ];

    protected $casts = [
        'date_engagement' => 'date',
        'montant_engage' => 'decimal:2',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
