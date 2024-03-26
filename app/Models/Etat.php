<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etat extends Model
{
    use HasFactory;

    protected $fillable = ['name_etat'];

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
