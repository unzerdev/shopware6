<?php

namespace UnzerSDK\test\Services;

use PHPUnit\Framework\TestCase;
use UnzerSDK\Services\JwtService;

class JwtServiceTest extends TestCase
{
    /**
     * @test
     */
    public function validatExpiryTimeShouldReturnTrueforFutureTime()
    {
        $currentTime = time();

        $payload = '{
          "nbf": 1738764790,
          "iat": 1738764793,
          "exp": ' . ($currentTime + 1) . ',
          "sub": "s-pub-2a10YswG8ly1BgaZd2vwkpi2wGPjL9hM",
          "role": "merchant"
        }';
        $decodedPayload = base64_encode($payload);
        $jwt = '{}.' . $decodedPayload . '.{}';

        self::assertTrue(JwtService::validateExpiryTime($jwt, 0));
    }

    /**
     * @test
     */
    public function validateExpiryTimeShouldReturnFalseIfExpFieldIsMissing()
    {
        $payload = '{
          "nbf": 1738764790,
          "iat": 1738764793,
          "sub": "s-pub-2a10YswG8ly1BgaZd2vwkpi2wGPjL9hM",
          "role": "merchant"
        }';
        $decodedPayload = base64_encode($payload);
        $jwt = '{}.' . $decodedPayload . '.{}';

        self::assertFalse(JwtService::validateExpiryTime($jwt, 0));
    }
}
