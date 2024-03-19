<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\TwoFactorAuthNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'image',
        'isActive',
        'code_2FA',
        'isEnable',
        'role_id'
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function role()
    {
        return $this->belongsTo(Role::class);
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function sendTwoFactorCodeEmailNotification($code, $role)
    {
        return $this->notify(new TwoFactorAuthNotification($code, $role));
    }

    public function generateRandomCode()
    {
        $code = null;
        $unique = false;

        while (!$unique) {
            $code = mt_rand(100000, 999999); // this should not be random
            $user = User::where('code_2FA', $code)->first();

            if (!$user) {
                $unique = true;
            }
        }
        return $code;
    }

    public function toggle ($bool)
    {
        $this->isEnable = $bool;
        $this->save();
    }

}
