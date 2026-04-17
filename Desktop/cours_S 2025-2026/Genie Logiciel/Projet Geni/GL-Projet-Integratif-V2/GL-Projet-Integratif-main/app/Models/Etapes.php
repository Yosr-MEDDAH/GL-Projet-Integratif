<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etapes extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id',
        'etat_id',
        'traitParRoleNom',
        'traitParId',
        'traitParNom',
    ];
    protected static function boot()
    {
        parent::boot();
 
        // Contrainte OCL : DateEtapePosterieure
        // context Etapes
        // inv DateEtapePosterieure:
        //   self.created_at > self.facture.dateReception
        static::creating(function ($etape) {
            EtapeConstraints::checkDateEtapePosterieure($etape);
        });
    }

    public function etat()
    {
        return $this->belongsTo(Etat::class);
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
