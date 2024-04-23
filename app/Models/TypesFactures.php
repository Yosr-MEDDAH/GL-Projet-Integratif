<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypesFactures extends Model
{
    use HasFactory;

    protected $fillable = [
        'typeName',
    ];


    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
