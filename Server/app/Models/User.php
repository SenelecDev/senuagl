<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'matricule',
        'email',
        'password',
        'phone',
        'department_id',
        'role_id',
        'manager_id',
        'is_active',
        'conges_annuels_total',
        'conges_annuels_restants',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_embauche' => 'date',
        'is_active' => 'boolean',
    ];

    // Relations
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function demandesConges()
    {
        return $this->hasMany(DemandeConge::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function demandesValidees()
    {
        return $this->hasMany(DemandeConge::class, 'valide_par');
    }

    // Accesseurs
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->name;
    }

    // Méthodes utiles
    public function isManager()
    {
        return $this->subordinates()->exists();
    }

    public function canValidateLeave()
    {
        return $this->role && in_array($this->role->nom, ['Superieur', 'Directeur RH', 'Directeur Unité', 'Responsable RH']);
    }
}
