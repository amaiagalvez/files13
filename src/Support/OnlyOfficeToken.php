<?php

namespace Files13\Support;

/**
 * The JSON Web Tokens OnlyOffice exchanges, signed with HS256 and the configured secret. Only HS256
 * is accepted: a token naming any other algorithm, or none, is refused.
 */
final class OnlyOfficeToken
{
    public static function secret(): ?string
    {
        $secret = config('files13.onlyoffice.jwt_secret');

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function encode(array $payload, string $secret): string
    {
        $header = self::base64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $body = self::base64(json_encode($payload, JSON_THROW_ON_ERROR));

        return $header.'.'.$body.'.'.self::base64(hash_hmac('sha256', $header.'.'.$body, $secret, true));
    }

    /**
     * The payload of a valid token, or null for a token that is malformed, signed with another
     * secret, of another algorithm or expired.
     *
     * @return array<string, mixed>|null
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;
        $headerData = json_decode(self::unbase64($header), true);

        if (! is_array($headerData) || ($headerData['alg'] ?? null) !== 'HS256') {
            return null;
        }

        if (! hash_equals(self::base64(hash_hmac('sha256', $header.'.'.$body, $secret, true)), $signature)) {
            return null;
        }

        $payload = json_decode(self::unbase64($body), true);

        if (! is_array($payload) || (isset($payload['exp']) && (int) $payload['exp'] < time())) {
            return null;
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private static function base64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unbase64(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
