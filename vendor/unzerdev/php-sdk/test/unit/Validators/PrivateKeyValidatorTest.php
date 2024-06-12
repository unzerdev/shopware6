<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the private key validator.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Validators;

use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\Validators\PrivateKeyValidator;

class PrivateKeyValidatorTest extends BasePaymentTest
{
    /**
     * Verify validate method behaves as expected.
     *
     * @test
     *
     * @dataProvider validateShouldReturnTrueIfPrivateKeyHasCorrectFormatDP
     *
     * @param string $key
     * @param bool   $expectedResult
     */
    public function validateShouldReturnTrueIfPrivateKeyHasCorrectFormat($key, $expectedResult): void
    {
        $this->assertEquals($expectedResult, PrivateKeyValidator::validate($key));
    }

    /**
     * Data provider for above test.
     *
     * @return array
     */
    public function validateShouldReturnTrueIfPrivateKeyHasCorrectFormatDP(): array
    {
        return [
            'valid sandbox' => ['s-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n', true],
            'valid production' => ['p-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n', true],
            'invalid public' => ['s-pub-2a10ifVINFAjpQJ9qW8jBe5OJPBx6Gxa', false],
            'invalid wrong format #1' => ['spriv-2a10an6aJK0Jg7sMdpu9gK7ih8pCccze', false],
            'invalid empty' => ['', false],
            'invalid null' => [null, false],
            'invalid missing postfix' => ['s-priv-', false],
            'invalid missing type' => ['s--2a10an6aJK0Jg7sMdpu9gK7ih8pCccze', false],
            'invalid wrong type' => ['s-foo-2a10an6aJK0Jg7sMdpu9gK7ih8pCccze', false]
        ];
    }
}
