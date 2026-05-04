<?php

namespace App\Models;

use App\Models\User;
use App\Models\Eleve;
use App\Models\Ecole;
use App\Models\Classe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory;

    protected $table = 'paiements';

    protected $fillable = [
        'parent_id', 'eleve_id', 'ecole_id', 'classe_id',
        'montant', 'statut', 'reference_transaction',
        'mode_paiement', 'date_paiement'
    ];

    protected $casts = [
        'date_paiement' => 'datetime',
        'montant' => 'decimal:2'
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }
}
