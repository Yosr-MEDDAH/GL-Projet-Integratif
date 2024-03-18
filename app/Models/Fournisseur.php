<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Fournisseur extends User
{
    use HasFactory;

    protected $fillable = [
        'idErp',
        'idFiscale',
        'adresse',
        'nationnalite',
    ];

}
