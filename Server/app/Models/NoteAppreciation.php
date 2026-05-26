<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteAppreciation extends Model
{
    protected $table = 'notes_appreciation';
    protected $primaryKey = 'id';

    protected $fillable = [
        'matricule_agent',
        'annee',
        'note',
        'commentaire'
    ];

    protected $casts = [
        'annee' => 'integer',
        'note' => 'integer',
    ];

    public $timestamps = true;

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'matricule_agent', 'matricule');
    }
}
