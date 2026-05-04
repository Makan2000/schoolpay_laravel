<?php

namespace App\Models;

use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Paiement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'ecole_id'];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
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
