<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avancement extends Model
{
    protected $table = 'avancements';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'date', 'motif', 'matricule_agent',
        'id_gf_ancien', 'id_gf_nouveau', 'id_nr_ancien', 'id_nr_nouveau'
    ];
    
    protected $casts = [
        'date' => 'date',
    ];
    
    public $timestamps = true;
    
    // Relations
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'matricule_agent', 'matricule');
    }
    
    public function gfAncien()
    {
        return $this->belongsTo(GF::class, 'id_gf_ancien', 'id_gf');
    }
    
    public function gfNouveau()
    {
        return $this->belongsTo(GF::class, 'id_gf_nouveau', 'id_gf');
    }
    
    public function nrAncien()
    {
        return $this->belongsTo(NR::class, 'id_nr_ancien', 'id_nr');
    }
    
    public function nrNouveau()
    {
        return $this->belongsTo(NR::class, 'id_nr_nouveau', 'id_nr');
    }
    
    // Accesseurs
    public function getTypeAvancementAttribute()
    {
        if ($this->id_gf_nouveau && $this->id_nr_nouveau) {
            return 'GF + NR';
        } elseif ($this->id_gf_nouveau) {
            return 'GF uniquement';
        } elseif ($this->id_nr_nouveau) {
            return 'NR uniquement';
        }
        return 'Indéfini';
    }
    
    public function getEstPromotionGfAttribute()
    {
        return !is_null($this->id_gf_nouveau);
    }
    
    public function getEstChangementNrAttribute()
    {
        return !is_null($this->id_nr_nouveau);
    }
}
