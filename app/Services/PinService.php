<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use App\Models\User;

class PinService
{
    /**
     * Check if the provided pin matches the user's pin.
     *
     * @param User $user
     * @param string $pin
     * @return bool
     */
    public function checkPin(User $user, string $pin): bool
    {
        // If you store the pin hashed (recommended)
        // return Hash::check($pin, $user->pin);

        // If you store the pin as plain text (not recommended)
        return $user->pin === $pin;
    }
}