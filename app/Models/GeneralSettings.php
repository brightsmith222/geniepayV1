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
        
    ];

    // In GeneralSettings model
protected $casts = [
    'is_enabled' => 'boolean',
];

}
