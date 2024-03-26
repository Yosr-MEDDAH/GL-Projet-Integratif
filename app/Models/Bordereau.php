<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bordereau extends Model
{
    use HasFactory;

    protected $table = 'bordereaux';

    protected $fillable = [
        'date_sent', 'folder', 'status', 'nature', 'reference'
    ];

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
