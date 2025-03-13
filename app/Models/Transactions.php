<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;

    protected $fillable = [
        'status', 
        'username',
        'service',
        'service_provider',
        'service_plan',
        'amount',
        'phone_number',
        'smart_card_number',
        'meter_number',
        'quantity',
        'electricity_token',
        'epin',
        'transaction_id',
        'image',
        'created_at',
        'updated_at',

    ];
}
