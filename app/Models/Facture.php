<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', // Numéro de facture
        'invoice_name', // Nom de la facture
        'organization', // Nom de l'organisation
        'department', // Nom du département
        'billing_date', // Date de facturation
        'consumption_period', // Période de consommation
        'currency', // Devise
        'amount', // Montant
        'invoice_file_path', // Chemin du fichier de la facture
        'reception_date', // Date de réception de la facture'
        'fournisseur_id' // ID du fournisseur
    ];

    public function fournisseur()
    {
        return $this->belongsTo(User::class)->where('role_id', 3);
    }

    public function etat()
    {
        return $this->belongsTo(Etat::class);
    }
}
