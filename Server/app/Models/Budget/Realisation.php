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
        'mois',
        'annee',
        'observation',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'montant_realise' => 'decimal:2',
            'mois' => 'integer',
            'annee' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Compte, $this>
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    /**
     * Prévision annuelle associée (même service, compte et année).
     */
    public function previsionAssociee(): ?BudgetPrevision
    {
        return BudgetPrevision::query()
            ->where('compte_id', $this->compte_id)
            ->where('annee', $this->annee)
            ->first();
    }

    /**
     * Écart = montant réalisé − montant prévu annuel (prorata mensuel optionnel : ici annuel plein).
     * Si aucune prévision : null.
     */
    public function ecartVersPrevisionAnnuelle(): ?string
    {
        $prevision = $this->previsionAssociee();
        if ($prevision === null) {
            return null;
        }

        return (string) ((float) $this->montant_realise - (float) $prevision->montant_prevu);
    }

    /**
     * Écart avec prorata de la prévision annuelle sur le mois (prévision / 12).
     */
    public function ecartVersPrevisionMensuelleProratis(): ?string
    {
        $prevision = $this->previsionAssociee();
        if ($prevision === null) {
            return null;
        }

        $prevuMensuel = (float) $prevision->montant_prevu / 12.0;

        return (string) ((float) $this->montant_realise - $prevuMensuel);
    }
}
