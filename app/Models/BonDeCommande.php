<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonDeCommande extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_commande', // Numéro de commande
        'created_by', // Créé par
        'idErp', // ID ERP
        'delai_paiement', // Délai de paiement
        'four_idFiscale',
        'fournisseur_id',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(User::class)->where('role_id', 3);
    }
}
