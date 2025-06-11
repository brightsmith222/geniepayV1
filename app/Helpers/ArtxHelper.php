<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class ArtxHelper
{
    public static function generateSalt($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function hashPassword($password, $salt)
    {
        $sha1 = sha1($password);
        return sha1($salt . $sha1); // or use sha512 if preferred
    }

    public static function request($command, $params = [])
    {
        $baseUrl = config('api.artx.base_url');
        $username = config('api.artx.username');
        $password = config('api.artx.password');

        $salt = self::generateSalt();
        $passwordHash = self::hashPassword($password, $salt);

        $payload = array_merge([
            'auth' => [
                'username' => $username,
                'salt' => $salt,
                'password' => $passwordHash
            ],
            'version' => 5,
            'command' => $command,
        ], $params);

        $response = Http::withoutVerifying()->post($baseUrl, $payload);

        return $response->json();
    }
}
