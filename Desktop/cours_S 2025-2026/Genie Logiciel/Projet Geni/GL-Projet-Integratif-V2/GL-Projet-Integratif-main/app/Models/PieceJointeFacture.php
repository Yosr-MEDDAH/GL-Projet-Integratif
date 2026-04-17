<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PieceJointeFacture extends Model
{
    use HasFactory;

    protected $fillable = [
        'namePJ',
        'createdBy'
    ];
}
