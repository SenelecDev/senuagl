<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Agent extends Model
{
    protected $table = 'agents';
    protected $primaryKey = 'matricule';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'matricule', 'titre', 'nom', 'prenom', 'sexe',
        'date_naissance', 'lieu_naissance', 'nationalite', 'num_identite', 'ethnie', 'religion',
        'situation_familiale', 'nombre_enfants', 'enfants_21_ans', 'enfants_18_ans',
        'part_trimf', 'part_ir', 'num_ipres', 'num_secu_social',
        'date_embauche', 'organisation', 'centre_de_responsabilite', 'lieu',
        'salaire_base', 'mode_reglement', 'banque', 'compte', 'domiciliation', 'rib',
        'syndicat', 'situation_affectation',
        'id_post', 'id_gf_actuel', 'id_nr_actuel'
    ];
    
    protected $casts = [
        'date_naissance' => 'date',
        'date_embauche' => 'date',
    ];
    
    public $timestamps = true;
    
    // Relations
    public function poste()
    {
        return $this->belongsTo(Poste::class, 'id_post', 'id_post');
    }
    
    public function gfActuel()
    {
        return $this->belongsTo(GF::class, 'id_gf_actuel', 'id_gf');
    }
    
    public function nrActuel()
    {
        return $this->belongsTo(NR::class, 'id_nr_actuel', 'id_nr');
    }
    
    public function avancements()
    {
        return $this->hasMany(Avancement::class, 'matricule_agent', 'matricule');
    }
    
    public function promotionsGF()
    {
        return $this->hasMany(Avancement::class, 'matricule_agent', 'matricule')
                    ->whereNotNull('id_gf_nouveau');
    }
    
    public function changementsNR()
    {
        return $this->hasMany(Avancement::class, 'matricule_agent', 'matricule')
                    ->whereNotNull('id_nr_nouveau');
    }
    
    // Accesseurs
    public function getAgeAttribute()
    {
        return Carbon::parse($this->date_naissance)->age;
    }
    
    public function getAncienneteAttribute()
    {
        return Carbon::parse($this->date_embauche)->diffInYears(Carbon::now());
    }
    
    public function getEstPlafonneAttribute()
    {
        if (!$this->poste || !$this->gfActuel) return false;
        
        $tubeMax = GF::find($this->poste->tube_max);
        return $this->gfActuel->ordre >= $tubeMax->ordre;
    }
    
    public function getDernierePromotionGfAttribute()
    {
        return $this->promotionsGF()->orderBy('date', 'desc')->first();
    }
    
    public function getDernierChangementNrAttribute()
    {
        return $this->changementsNR()->orderBy('date', 'desc')->first();
    }
    
    // Vérifications
    public function estEligibleRetraite($ageLimite = 60)
    {
        return $this->age >= $ageLimite;
    }
    
    public function peutEtrePromuGf()
    {
        if (!$this->poste || !$this->gfActuel) return false;
        
        $tubeMax = GF::find($this->poste->tube_max);
        return $this->gfActuel->ordre < $tubeMax->ordre;
    }
}
