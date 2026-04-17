<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectionsRegionales extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomDirectionsRegionales',
        'Responsable',
        'created_by',
    ];
}
