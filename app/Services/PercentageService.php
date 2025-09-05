<?php

namespace App\Services;

use App\Models\AirtimeTopupPercentage;
use App\Models\DataTopupPercentage;
use App\Models\VoucherPercentage;
use App\Models\GeneralSettings;

use Illuminate\Support\Facades\Log;

class PercentageService
{
    // Calculate discounted amount for airtime topup.
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

    // Calculate discounted amount for international airtime topup.
    public function calculateIntAirtimeDiscountedAmount(float $originalAmount): float
{
    $record = AirtimeTopupPercentage::where('network_name', 'INTERNATIONAL')->first();

        if ($record && (bool) $record->status) {
            $percentage = (float) $record->network_percentage;
            return $originalAmount + $this->calculateMarkup($percentage, $originalAmount);
        }

        if (!$record) {
            Log::warning("AirtimeTopupPercentage record not found for international airtime");
        } elseif (!(bool) $record->status) {
            Log::info("AirtimeTopupPercentage is disabled for international airtime");
        }

        return $originalAmount;
    }

    // Calculate discounted amount for data topup.
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
            return $originalAmount + $this->calculateDiscount($percentage, $originalAmount);
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
            return $originalAmount + $this->calculateDiscount($percentage, $originalAmount);
        }

        if (!$record) {
            Log::warning("EsimPercentage record not found for network ID: $networkId");
        } elseif (!(bool) $record->status) {
            Log::info("EsimPercentage is disabled for network ID: $networkId");
        }

        return $originalAmount;
    }

    // Calculate discounted amount for Smile data topup.
    public function calculateSmileDiscountedAmount(float $originalAmount): float
{
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

// Calculate discounted amount for Spectranet data topup.
public function calculateSpectranetDiscountedAmount(float $originalAmount): float
{
    $record = DataTopupPercentage::where('network_name', 'spectranet')->first();

    if ($record && (bool) $record->status) {
        $percentage = (float) $record->network_percentage;

        // Log the percentage and original amount
        Log::info("Applying SpectranetPercentage for network name: spectranet", [
            'percentage' => $percentage,
            'original_amount' => $originalAmount
        ]);

        return $originalAmount + $this->calculateDiscount($percentage, $originalAmount);
    }

    if (!$record) {
        Log::warning("DataTopupPercentage record not found for network name: spectranet");
    } elseif (!(bool) $record->status) {
        Log::info("DataTopupPercentage is disabled for network name: spectranet");
    }

    // Return the original amount if no percentage is found or disabled
    return $originalAmount;
}

// Calculate discounted amount for international data topup.
public function calculateInternationalDiscountedAmount(float $originalAmount): float
{
    $record = DataTopupPercentage::where('network_name', 'international')->first();

    if ($record && (bool) $record->status) {
        $percentage = (float) $record->network_percentage;

        // Log the percentage and original amount
        Log::info("Applying InternationalPercentage for network name: international", [
            'percentage' => $percentage,
            'original_amount' => $originalAmount
        ]);

        return $originalAmount + $this->calculateDiscount($percentage, $originalAmount);
    }

    if (!$record) {
        Log::warning("DataTopupPercentage record not found for network name: international");
    } elseif (!(bool) $record->status) {
        Log::info("DataTopupPercentage is disabled for network name: international");
    }

    // Return the original amount if no percentage is found or disabled
    return $originalAmount;
}

// Calculate virtual charge.
public function virtualCharge(float $originalAmount): float
{
    $record = GeneralSettings::where('name', 'virtual_charge')->first();

    if ($record && (bool) $record->is_enabled) {
        $percentage = (float) $record->referral_bonus;
        return $originalAmount - $this->calculateDiscount($percentage, $originalAmount);
    }

    if (!$record) {
        Log::warning("Virtual charge not found");
    } elseif (!(bool) $record->is_enabled) {
        Log::info("Virtual charge is disabled");
    }

    return $originalAmount;
}

/**
 * Calculate Paystack payment charge.
 */
public function calculatePaystackCharge(float $originalAmount): float
{
    $setting = GeneralSettings::where('name', 'card_payment')->first();

    // Only apply charge if card payment is enabled
    if ($setting && (bool) $setting->is_enabled && (float) $setting->giftcard_percentage > 0) {
        $percentage = (float) $setting->giftcard_percentage;
        $charge = ($percentage / 100) * $originalAmount;
        return $originalAmount + $charge;
    }

    // If not enabled or no percentage, return the original amount
    return $originalAmount;
}

    /**
 * Generate a unique ticket ID like "TKT-25635"
 *
 * @return string
 */
public static function generateTicketId()
{
    $prefix = 'TKT-';
    $number = random_int(10000, 99999); // 5-digit random number
    return $prefix . $number;
}

    /**
     * Calculate discount based on percentage.
     */
    private function calculateDiscount(float $percentage, float $amount): float
    {
        return ($percentage / 100) * $amount;
    }

    // Markup calculator
private function calculateMarkup(float $percentage, float $amount): float
{
    return ($percentage / 100) * $amount;
}
}
