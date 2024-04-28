<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectionsCentrales extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomDirectionsCentrales',
        'directeur',
        'profil',
        'created_by',
    ];
}
