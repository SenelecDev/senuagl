<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NR extends Model
{
    protected $table = 'nrs';
    protected $primaryKey = 'id_nr';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = ['id_nr', 'ordre'];
    
    public $timestamps = true;
    
    // Relations
    public function agents()
    {
        return $this->hasMany(Agent::class, 'id_nr_actuel', 'id_nr');
    }
    
    public function avancementsAncien()
    {
        return $this->hasMany(Avancement::class, 'id_nr_ancien', 'id_nr');
    }
    
    public function avancementsNouveau()
    {
        return $this->hasMany(Avancement::class, 'id_nr_nouveau', 'id_nr');
    }
}