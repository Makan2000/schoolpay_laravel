<?php

namespace App\Models;

use App\Models\Eleve;
use App\Models\Paiement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom', 'email', 'password', 'role', 'telephone'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function eleves()
    {
        return $this->hasMany(Eleve::class, 'parent_id');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'parent_id');
    }

    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    public function isParent()
    {
        return $this->role === 'parent';
    }
}
