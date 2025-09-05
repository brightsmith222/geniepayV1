<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'provider_name',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get service status for a specific service type and provider
     */
    public static function getServiceStatus($serviceType, $providerName)
    {
        $service = self::where('service_type', $serviceType)
                      ->where('provider_name', $providerName)
                      ->first();
        
        return $service ? $service->is_active : true; // Default to active if not found
    }

    /**
     * Toggle service status for a specific service type and provider
     */
    public static function toggleServiceStatus($serviceType, $providerName)
    {
        $service = self::updateOrCreate(
            [
                'service_type' => $serviceType,
                'provider_name' => $providerName
            ],
            [
                'is_active' => !self::getServiceStatus($serviceType, $providerName)
            ]
        );

        return $service->is_active;
    }

    /**
     * Set service status for a specific service type and provider
     */
    public static function setServiceStatus($serviceType, $providerName, $isActive)
    {
        return self::updateOrCreate(
            [
                'service_type' => $serviceType,
                'provider_name' => $providerName
            ],
            [
                'is_active' => $isActive
            ]
        );
    }
}
