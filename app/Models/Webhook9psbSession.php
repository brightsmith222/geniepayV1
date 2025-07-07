<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook9psbSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'account_number',
        'amount',
        'status',
        'raw_response'
    ];
}
