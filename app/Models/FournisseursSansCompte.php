<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;

class FournisseursSansCompte extends Model
{
    use HasFactory, Notifiable;



    public function generateRandomRefreshToken()
    {
        $password = null;
        $unique = false;

        while (!$unique) {
            $password =  Str::random(9);
            $user = User::where('refresh_token', $password)->first();

            if (!$user) {
                $unique = true;
            }
        }
        return $password;
    }
}
