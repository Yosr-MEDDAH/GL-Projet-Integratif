<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'num_facture',
        'id_facture',
        'id_reclamation',
        'titre_reclamation',
        'nom_creator',
        'lu',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
