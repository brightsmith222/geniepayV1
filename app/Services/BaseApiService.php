<?php

namespace App\Services;

use App\Models\GeneralSettings;

abstract class BaseApiService
{
    protected $serviceName;
    protected $headers;
    
    public function getNetworkPrefixes(): array
    {
        return [];
    }

    public function isEnabled(): bool
    {
        return (bool) GeneralSettings::where('name', $this->serviceName)->value('is_enabled');
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function validateNumberForNetwork(string $phoneNumber, int $network): bool
    {
        $prefixes = $this->getNetworkPrefixes();
        
        // Skip validation if no prefixes are defined for this service
        if (empty($prefixes)) {
            return true;
        }
        $prefixLength = $this->getPrefixLength();
        
        $normalizedNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        $numberPrefix = substr($normalizedNumber, 0, $prefixLength);
        
        return in_array($numberPrefix, $prefixes[$network] ?? []);
    }
    
    protected function getPrefixLength(): int
    {
        return 4; // Default to GladTidings format
    }
    
}