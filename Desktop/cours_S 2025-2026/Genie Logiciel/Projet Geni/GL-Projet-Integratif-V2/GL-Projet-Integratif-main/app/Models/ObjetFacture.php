<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjetFacture extends Model
{
    use HasFactory;

    protected $fillable = [
        'objet_name',
        'createdBy'
    ];


    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
