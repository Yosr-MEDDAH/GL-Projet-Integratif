<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'invoice_name',
        'organization',
        'department',
        'billing_date',
        'consumption_period',
        'currency',
        'amount',
        'type_facture_id',
        'invoice_file_path',
        'reception_date',
        'payment_period',
        'fournisseur_id',
        'borderau_id',
        'bon_de_commande_id',
        'etat_id', // Ajout du champ 'etat_id'
        'isArchived',
        'created_by',
        'agent_bof_id',
        'pieces_jointes',
        'objet_facture_id',
    ];

    public function typeFacture()
    {
        return $this->belongsTo(TypesFactures::class, 'type_facture_id');
    }

    public function fournisseur()
    {
        return $this->belongsTo(User::class)->where('role_id', 3);
    }

    public function etat()
    {
        return $this->belongsTo(Etat::class);
    }

    public function bordereau()
    {
        return $this->belongsTo(Bordereau::class);
    }

    public function bonDeCommande()
    {
        return $this->belongsTo(BonDeCommande::class);
    }

    public function etapes()
    {
        return $this->hasMany(Etapes::class);
    }

    public function objetFacture()
    {
        return $this->belongsTo(ObjetFacture::class, 'objet_facture_id');
    }

    protected $casts = [
        'pieces_jointes' => 'array',
    ];
}
