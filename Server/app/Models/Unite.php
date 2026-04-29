<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unite extends Model
{
    protected $table = 'unites';
    protected $primaryKey = 'id_unite';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = ['id_unite', 'nom', 'type', 'id_parent'];
    
    public $timestamps = true;
    
    // Relations
    public function parent()
    {
        return $this->belongsTo(Unite::class, 'id_parent', 'id_unite');
    }
    
    public function enfants()
    {
        return $this->hasMany(Unite::class, 'id_parent', 'id_unite');
    }
    
    public function postes()
    {
        return $this->hasMany(Poste::class, 'id_unite', 'id_unite');
    }
    
    // Accesseurs
    public function getCheminCompletAttribute()
    {
        $chemin = $this->nom;
        if ($this->parent) {
            $chemin = $this->parent->chemin_complet . ' > ' . $chemin;
        }
        return $chemin;
    }
}