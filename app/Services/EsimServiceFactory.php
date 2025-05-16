<?php 

namespace App\Services;

use App\Models\GeneralSettings;

class EsimServiceFactory
{
    public static function getActiveService(): ?EsimServiceInterface
    {
        // Example logic to determine the active service
        $serviceName = new ArtxEsimService();

        if ($serviceName->isEnabled()) {
            return $serviceName;
        }

        return null;
    }
}


