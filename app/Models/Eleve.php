<?php

namespace App\Models;

use App\Models\User;
use App\Models\Ecole;
use App\Models\Classe;
use App\Models\Paiement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eleve extends Model
{
    use HasFactory;

    protected $table = 'eleves';

    protected $fillable = ['nom', 'prenom', 'date_naissance', 'parent_id', 'ecole_id', 'classe_id'];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }
}
