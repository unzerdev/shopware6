<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the resource name service.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Services;

use UnzerSDK\Services\ResourceNameService;
use UnzerSDK\test\BasePaymentTest;

class ResourceNameServiceTest extends BasePaymentTest
{
    /**
     * Verify getting the short name of a class.
     *
     * @test
     *
     * @dataProvider classShortNameTestDP
     *
     * @param string $className
     * @param string $expected
     */
    public function shouldReturnTheCorrectShortName($className, $expected): void
    {
        $this->assertEquals($expected, ResourceNameService::getClassShortNameKebapCase($className));
    }

    /**
     * @return array
     */
    public function classShortNameTestDP(): array
    {
        return [
            'normal class name' => ['className' => 'Path\\To\\Test\\Class', 'expected' => 'class'],
            'camel case class' => ['className' => 'Path\\To\\Test\\CamelCaseClass', 'expected' => 'camel-case-class'],
            'upper case class' => ['className' => 'Path\\To\\Test\\CCC', 'expected' => 'ccc']
        ];
    }
}
