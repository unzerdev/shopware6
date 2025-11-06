<?php

namespace UnzerSDK\Services;

class JwtService
{
    public const Expiry_Buffer = 60;

    public static function validateExpiryTime(string $jwt, int $expiryBufferSeconds = self::Expiry_Buffer): bool
    {
        $jwtData = self::extractPayload($jwt);
        $expireTime = $jwtData['exp'] ?? 0;
        return ($expireTime - $expiryBufferSeconds) > time();
    }

    private static function extractPayload(string $jwt): ?array
    {
        $tokenSegments = explode('.', $jwt);
        return json_decode(base64_decode($tokenSegments[1]), true);
    }
}
