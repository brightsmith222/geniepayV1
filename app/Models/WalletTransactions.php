<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransactions extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'username',
        'trans_type',
        'service',
        'transaction_id',
        'amount',
        'sender_email',
        'receiver_email',
        'sender_name',
        'receiver_name',
        'status',
        'balance_before',
        'balance_after',
    ];
}
