<?php
/*
 *  Test class for ApplepaySession.
 *
 *  @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Adapter;

use PHPUnit\Framework\TestCase;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;

class ApplepaySessionTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $applepaySession = new ApplepaySession('merchantIdentifier', 'displayName', 'domainName');
        $expectedJson = '{"merchantIdentifier": "merchantIdentifier", "displayName": "displayName", "domainName": "domainName"}';

        $jsonSerialize = $applepaySession->jsonSerialize();
        $this->assertJsonStringEqualsJsonString($expectedJson, $jsonSerialize);
    }
}
