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
        return Hash::check($pin, $user->pin);
    }
}