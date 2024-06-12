<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the key validator.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Validators;

use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\Validators\PublicKeyValidator;

class PublicKeyValidatorTest extends BasePaymentTest
{
    /**
     * Verify validate method behaves as expected.
     *
     * @test
     *
     * @dataProvider validateShouldReturnTrueIfPublicKeyHasCorrectFormatDP
     *
     * @param string $key
     * @param bool   $expectedResult
     */
    public function validateShouldReturnTrueIfPublicKeyHasCorrectFormat($key, $expectedResult): void
    {
        $this->assertEquals($expectedResult, PublicKeyValidator::validate($key));
    }

    /**
     * Data provider for above test.
     *
     * @return array
     */
    public function validateShouldReturnTrueIfPublicKeyHasCorrectFormatDP(): array
    {
        return [
            'valid sandbox' => ['s-pub-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n', true],
            'valid production' => ['p-pub-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n', true],
            'invalid public' => ['s-priv-2a10ifVINFAjpQJ9qW8jBe5OJPBx6Gxa', false],
            'invalid wrong format #1' => ['spub-2a10an6aJK0Jg7sMdpu9gK7ih8pCccze', false],
            'invalid empty' => ['', false],
            'invalid null' => [null, false],
            'invalid missing postfix' => ['s-pub-', false],
            'invalid missing type' => ['s--2a10an6aJK0Jg7sMdpu9gK7ih8pCccze', false],
            'invalid wrong type' => ['s-foo-2a10an6aJK0Jg7sMdpu9gK7ih8pCccze', false]
        ];
    }
}
