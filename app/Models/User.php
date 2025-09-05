<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password',
        'wallet_balance',
        'status',
        'phone_number',
        'gender',
        'pin',
        'role',
        'image',
        'referred_by',
        'referral_bonus_given',
        'referral_bonus_eligible',
        'last_login_at',

    ];

    public function isAdmin()
{
    return $this->role == 1; 
}


public function transactions()
{
    return $this->hasMany(Transactions::class, 'username', 'username');
}

public function walletTransactions()
{
    return $this->hasMany(WalletTransactions::class, 'username', 'username');
}

public function user()
{
    return $this->belongsTo(User::class, 'username', 'username');
}

public function referrer()
{
    return $this->belongsTo(User::class, 'referred_by');
}

// Get the users referred by this user
public function referrals()
{
    return $this->hasMany(User::class, 'referred_by');
}

public function referralBonuses()
{
    return $this->hasMany(ReferralBonus::class, 'referrer_id');
}

/**
 * Get the tickets for the user.
 */
public function tickets()
{
    return $this->hasMany(Ticket::class);
}

/**
 * Get the ticket replies for the user.
 */
public function ticketReplies()
{
    return $this->hasMany(TicketReply::class);
}

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',

        ];
    }
}
