<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_enabled',
        'referral_bonus',
        'giftcard_percentage',
        'virtual_percentage',
        'card_charge',
    ];

    // In GeneralSettings model
    protected $casts = [
        'is_enabled' => 'boolean',
        'referral_bonus' => 'decimal:2',
        'giftcard_percentage' => 'decimal:2',
        'virtual_percentage' => 'decimal:2',
        'card_charge' => 'decimal:2',
    ];

}
