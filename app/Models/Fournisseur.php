<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Fournisseur extends User
{
    use HasFactory;

    protected $table = 'users';

    public $fillable = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->fillable = array_merge(parent::getFillable(), [
            'idErp',
            'idFiscale',
            'adress',
            'nationnalites',

        ]);
    }
    public function getFillable()
    {
        return array_merge(parent::getFillable(), [
            'idErp',
            'idFiscale',
            'adress',
            'nationnalites',
        ]);
    }

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    public function reclamations()
    {
        return $this->hasMany(Reclamation::class);
    }
}
