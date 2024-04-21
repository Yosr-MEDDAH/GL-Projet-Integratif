<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reclamation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', // Titre de la réclamation
        'text', // Texte de la réclamation
        'path', // Chemin du fichier de la réclamation
        'file',
        'etat',
        'attached_file', // Nom du fichier joint à la réclamation
        'fournisseur_id',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(User::class)->where('role_id', 3);
    }
}
