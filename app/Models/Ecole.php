<?php

namespace App\Models;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Paiement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ecole extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'email', 'telephone', 'adresse'];

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function eleves()
    {
        return $this->hasMany(Eleve::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }
}
