<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeConge extends Model
{
    use HasFactory;
    protected $table = 'demandes_conges';

    protected $fillable = [
        'user_id',
        'type_demande',
        'date_debut',
        'date_fin',
        'duree_jours',
        'motif',
        'commentaire',
        'statut',
        'valide_par',
        'date_validation',
        'commentaire_validation',
        'signatures',
        'pieces_jointes',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_validation' => 'datetime',
        'signatures' => 'array',
        'pieces_jointes' => 'array',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    // Scopes
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeApprouve($query)
    {
        return $query->where('statut', 'approuve');
    }

    public function scopeRejete($query)
    {
        return $query->where('statut', 'rejete');
    }

    // Accesseurs
    public function getStatutLabelAttribute()
    {
        return [
            'en_attente' => 'En attente',
            'approuve' => 'Approuvé',
            'rejete' => 'Rejeté',
            'annule' => 'Annulé',
        ][$this->statut] ?? $this->statut;
    }

    public function getTypeLabelAttribute()
    {
        return [
            'conge_annuel' => 'Congé annuel',
            'conge_maladie' => 'Congé maladie',
            'conge_maternite' => 'Congé maternité',
            'conge_paternite' => 'Congé paternité',
            'conge_sans_solde' => 'Congé sans solde',
            'absence_exceptionnelle' => 'Absence exceptionnelle',
            'report_conge' => 'Report de congé',
        ][$this->type_demande] ?? $this->type_demande;
    }
}
