<?php

namespace UnzerSDK\test\unit\Services;

use UnzerSDK\Services\IdService;
use UnzerSDK\test\BasePaymentTest;

class IdServiceTest extends BasePaymentTest
{
    /**
     * @test
     *
     * @dataProvider validUUIDsDP
     *
     * @param mixed $id
     */
    public function idWithUniqueIdReturnsTrue(string $id)
    {
        $isUUID = IdService::isUUIDResource($id);
        $this->assertTrue($isUUID);
    }

    /**
     * @test
     *
     * @dataProvider invalidUUIDsDP
     *
     * @param string $id
     */
    public function shortIdShouldReturnFalse(string $id)
    {
        $isUUID = IdService::isUUIDResource($id);
        $this->assertFalse($isUUID);
    }

    public function validUUIDsDP(): array
    {
        return [
            ['s-bsk-123e4567-e89b-12d3-a456-426614174000'],
            ['s-cst-123e4567-e89b-12d3-a456-426614174001'],
            ['p-bsk-123e4567-e89b-12d3-a456-426614174000'],
            ['p-cst-123e4567-e89b-12d3-a456-426614174001'],
        ];
    }

    public function invalidUUIDsDP(): array
    {
        return [
            [''],
            ['s-cst'],
            ['s-bsk-123e4567-e89b-12d3-a456-426614174000-'],
            ['-s-bsk-123e4567-e89b-12d3-a456-426614174000'],
            ['s-bskt-123e4567-e89b-12d3-a456-426614174000'],
            ['a-bsk-123e4567-e89b-12d3-a456-426614174000'],
            ['s-cst-123456abcdef'],
        ];
    }
}
