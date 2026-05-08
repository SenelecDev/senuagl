<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poste extends Model
{
    protected $table = 'postes';
    protected $primaryKey = 'id_post';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = ['id_post', 'intitule', 'effectif_theorique', 'tube_min', 'tube_max', 'id_unite'];
    
    public $timestamps = true;
    
    // Relations
    public function unite()
    {
        return $this->belongsTo(Unite::class, 'id_unite', 'id_unite');
    }
    
    public function tubeMin()
    {
        return $this->belongsTo(GF::class, 'tube_min', 'id_gf');
    }
    
    public function tubeMax()
    {
        return $this->belongsTo(GF::class, 'tube_max', 'id_gf');
    }
    
    public function agents()
    {
        return $this->hasMany(Agent::class, 'id_post', 'id_post');
    }
    
    // Accesseurs
    public function getEffectifReelAttribute()
    {
        return $this->agents()->count();
    }
    
    public function getPostesVacantsAttribute()
    {
        return max(0, $this->effectif_theorique - $this->effectif_reel);
    }
    
    public function getTauxOccupationAttribute()
    {
        if ($this->effectif_theorique == 0) return 0;
        return round(($this->effectif_reel / $this->effectif_theorique) * 100, 1);
    }
    
    // Vérification plafonnement
    public function estDansTube($gfCode)
    {
        $gf = GF::find($gfCode);
        if (!$gf) return false;
        
        $ordreMin = GF::find($this->tube_min)->ordre;
        $ordreMax = GF::find($this->tube_max)->ordre;
        
        return $gf->ordre >= $ordreMin && $gf->ordre <= $ordreMax;
    }
}
