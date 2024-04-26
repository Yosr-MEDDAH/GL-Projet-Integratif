<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\TwoFactorAuthNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
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
        "code_2fa_created_at",
        'isTwoFactorEnabled',
        'role_id',
        'refresh_token',
        'refreshToken_created_at',
        'type_facture_ids'
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'code_2FA',
        "code_2fa_created_at",
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'type_facture_ids' => 'array',
    ];

    public function etapes()
    {
        return $this->hasMany(Etapes::class);
    }

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

    public function sendTwoFactorCodeEmailNotification($code, $name)
    {
        return $this->notify(new TwoFactorAuthNotification($code, $name));
    }

    public function sendResetPasswordNotification($code)
    {
        return $this->notify(new ResetPasswordNotification($code));
    }

    public function generateRandomCode()
    {
        $code = null;
        $unique = false;

        while (!$unique) {
            $code = mt_rand(100000, 999999);
            $user = User::where('code_2FA', $code)->first();

            if (!$user) {
                $unique = true;
            }
        }
        return $code;
    }

    public function generateRandomRefreshToken()
    {
        $refreshToken = null;
        $unique = false;

        while (!$unique) {
            $refreshToken =  Str::random(60);
            $user = User::where('refresh_token', $refreshToken)->first();

            if (!$user) {
                $unique = true;
            }
        }
        return $refreshToken;
    }

    public function generateRandomResetToken()
    {
        $resetToken = null;
        $unique = false;

        while (!$unique) {
            $resetToken =  Str::random(60);

            $record = DB::table('password_reset_tokens')
                ->where('token', $resetToken)
                ->first();
            if (!$record) {
                $unique = true;
            }
        }
        return $resetToken;
    }


    public function toggle($bool)
    {
        $this->isTwoFactorEnabled = $bool;
        $this->save();
    }

    public function getFillable()
    {
        return [
            'name',
            'email',
            'password',
            'phone',
            'image',
            'isActive',
            'code_2FA',
            'isTwoFactorEnabled',
            'role_id',
            'refresh_token',
            'refreshToken_created_at'
        ];
    }
}
