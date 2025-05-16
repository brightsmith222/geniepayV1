<?php

namespace App\Services;

class ApiServiceFactory
{
    public static function create(string $serviceName, string $serviceType = 'airtime'): ?ApiServiceInterface
    {
        switch ($serviceType) {
            case 'data':
                return match ($serviceName) {
                    'glad' => new GladDataService(),
                    'artx' => new ArtxDataService(),
                    default => null
                };
            
            case 'airtime':
            default:
                return match ($serviceName) {
                    'glad' => new GladAirtimeService(),
                    'artx' => new ArtxAirtimeService(),
                    default => null
                };
        }
    }
}
