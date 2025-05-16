<?php

namespace App\Services;

use App\Models\ReferralBonus;
use App\Models\GeneralSettings;
use App\Models\User;

class ReferralService
{
    public function handleFirstTransactionBonus(User $user, string $service, float $amount)
{
    // Check if referral service is enabled
    $isReferralEnabled = GeneralSettings::where('name', 'referral')->value('is_enabled');
    if (!$isReferralEnabled) {
        return;
    }

    // Check if user is eligible for a referral bonus
    if (!$user->referral_bonus_eligible) {
        return; 
    }

    // Check if user has a referrer
    $referrer = $user->referrer;
    if (!$referrer) {
        return;
    }

    // Don't give bonus if already given
    if ($user->referral_bonus_given) {
        return;
    }

    $bonusAmount = $this->referralBonus($amount);

    ReferralBonus::create([
        'referrer_id' => $referrer->id,
        'referred_user_id' => $user->id,
        'bonus_amount' => $bonusAmount,
        'service' => $service,
    ]);

    // Update referrer balance, notify etc...
    $referrer->increment('wallet_balance', $bonusAmount);

    // Mark bonus as given
    $user->referral_bonus_given = true;
    $user->save();
}

public function referralBonus(float $amount): float
{
   // Retrieve the referral bonus value from the database
   $referralBonus = GeneralSettings::where('name', 'referral')->value('referral_bonus');

   return $referralBonus ?? 0;
}



}
