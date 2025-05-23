<?php

namespace App\Services;

use App\Models\AirtimeTopupPercentage;
use App\Models\DataTopupPercentage;
use App\Models\VoucherPercentage;

use Illuminate\Support\Facades\Log;

class PercentageService
{
    public function calculateDiscountedAmount(int $networkId, float $originalAmount): float
    {
        $record = AirtimeTopupPercentage::where('network_id', $networkId)->first();

        if ($record && (bool) $record->status) {
            $percentage = (float) $record->network_percentage;
            return $originalAmount - $this->calculateDiscount($percentage, $originalAmount);
        }

        if (!$record) {
            Log::warning("AirtimeTopupPercentage record not found for network ID: $networkId");
        } elseif (!(bool) $record->status) {
            Log::info("AirtimeTopupPercentage is disabled for network ID: $networkId");
        }

        return $originalAmount;
    }

    public function calculateDataDiscountedAmount(int $networkId, float $originalAmount): float
    {
        $record = DataTopupPercentage::where('network_id', $networkId)->first();

        if ($record && (bool) $record->status) {
            $percentage = (float) $record->network_percentage;
            return $originalAmount + $this->calculateDiscount($percentage, $originalAmount);
        }

        if (!$record) {
            Log::warning("DataTopupPercentage record not found for network ID: $networkId");
        } elseif (!(bool) $record->status) {
            Log::info("DataTopupPercentage is disabled for network ID: $networkId");
        }

        return $originalAmount;
    }

     /**
     * Calculate discounted amount for gift cards.
     */
    public function calculateGiftCardDiscountedAmount(int $networkId, float $originalAmount): float
    {
        $record = VoucherPercentage::where('network_id', $networkId)->first();

        if ($record && (bool) $record->status) {
            $percentage = (float) $record->network_percentage;
            return $originalAmount - $this->calculateDiscount($percentage, $originalAmount);
        }

        if (!$record) {
            Log::warning("GiftCardPercentage record not found for network ID: $networkId");
        } elseif (!(bool) $record->status) {
            Log::info("GiftCardPercentage is disabled for network ID: $networkId");
        }

        return $originalAmount;
    }

    /**
     * Calculate discounted amount for eSIM.
     */
    public function calculateEsimDiscountedAmount(int $networkId, float $originalAmount): float
    {
        $record = VoucherPercentage::where('network_id', $networkId)->first();

        if ($record && (bool) $record->status) {
            $percentage = (float) $record->network_percentage;
            return $originalAmount - $this->calculateDiscount($percentage, $originalAmount);
        }

        if (!$record) {
            Log::warning("EsimPercentage record not found for network ID: $networkId");
        } elseif (!(bool) $record->status) {
            Log::info("EsimPercentage is disabled for network ID: $networkId");
        }

        return $originalAmount;
    }

    public function calculateSmileDiscountedAmount(float $originalAmount): float
{
    // Retrieve the percentage for Smile from the DataTopupPercentage table
    $record = DataTopupPercentage::where('network_name', 'smile')->first();

    if ($record && (bool) $record->status) {
        $percentage = (float) $record->network_percentage;
        return $originalAmount + $this->calculateDiscount($percentage, $originalAmount);
    }

    if (!$record) {
        Log::warning("DataTopupPercentage record not found for network name: smile");
    } elseif (!(bool) $record->status) {
        Log::info("DataTopupPercentage is disabled for network name: smile");
    }

    // Return the original amount if no percentage is found or disabled
    return $originalAmount;
}

    /**
     * Calculate discount based on percentage.
     */
    private function calculateDiscount(float $percentage, float $amount): float
    {
        return ($percentage / 100) * $amount;
    }
}
