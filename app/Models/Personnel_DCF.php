<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Personnel_DCF extends User
{
    use HasFactory;

    protected $fillable = [
        'direction'
    ];

   
}
