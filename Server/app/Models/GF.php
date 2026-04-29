<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GF extends Model
{
    protected $table = 'gfs';
    protected $primaryKey = 'id_gf';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = ['id_gf', 'ordre'];
    
    public $timestamps = true;
    
    // Relations
    public function agents()
    {
        return $this->hasMany(Agent::class, 'id_gf_actuel', 'id_gf');
    }
    
    public function postesMin()
    {
        return $this->hasMany(Poste::class, 'tube_min', 'id_gf');
    }
    
    public function postesMax()
    {
        return $this->hasMany(Poste::class, 'tube_max', 'id_gf');
    }
    
    public function avancementsAncien()
    {
        return $this->hasMany(Avancement::class, 'id_gf_ancien', 'id_gf');
    }
    
    public function avancementsNouveau()
    {
        return $this->hasMany(Avancement::class, 'id_gf_nouveau', 'id_gf');
    }
}