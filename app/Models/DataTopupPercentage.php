<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataTopupPercentage extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_name',
        'network_percentage',
        'status',
    ];
}
