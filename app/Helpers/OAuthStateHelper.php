<?php

namespace App\Helpers;

class OAuthStateHelper
{
    /**
     * Generate a signed, tamper-proof OAuth state parameter.
     */
    public static function generate(array $data): string
    {
        $json = json_encode($data);
        $signature = hash_hmac('sha256', $json, config('app.key'));
        return base64_encode($signature . '|' . $json);
    }

    /**
     * Validate and decode a signed OAuth state parameter.
     *
     * @return array|null The decoded state data, or null if invalid/tampered.
     */
    public static function validate(string $state): ?array
    {
        $decoded = base64_decode(str_replace(' ', '+', $state), true);

        if ($decoded === false || !str_contains($decoded, '|')) {
            return null;
        }

        [$signature, $json] = explode('|', $decoded, 2);

        $expected = hash_hmac('sha256', $json, config('app.key'));

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return null;
        }

        return $data;
    }
}
