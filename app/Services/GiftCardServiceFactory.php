<?php 

namespace App\Services;

use App\Models\GeneralSettings;

class GiftCardServiceFactory
{
    public static function getActiveService(): ?GiftCardServiceInterface
    {
        // Example logic to determine the active service
        $serviceName = new ArtxGiftCardService();

        if ($serviceName->isEnabled()) {
            return $serviceName;
        }

        return null;
    }
}


