<?php

namespace App\Models;

use App\Notifications\NotificationCredentials;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;

class FournisseursSansCompte extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'idErp',
        'idFiscale',
        'adress',
        'nationnalites',
    ];
    



    public function generateRandomPassword()
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


    public function NotificationCredentials($email, $password)
    {
        return $this->notify(new NotificationCredentials($email, $password));
    }
}
