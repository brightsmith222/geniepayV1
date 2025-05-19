<?php

namespace App\Services;

class ApiServiceFactory
{
    public static function create(string $serviceName, string $serviceType = 'airtime'): ?ApiServiceInterface
    {
        switch ($serviceType) {
            case 'data':
                return match ($serviceName) {
                    'glad_data' => new GladDataService(),
                    'artx_data' => new ArtxDataService(),
                    default => null
                };
            
            case 'airtime':
            default:
                return match ($serviceName) {
                    'glad_airtime' => new GladAirtimeService(),
                    'artx_airtime' => new ArtxAirtimeService(),
                    default => null
                };
        }
    }
}
