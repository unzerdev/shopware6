<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the Webhook resource.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources;

use UnzerSDK\Resources\Webhook;
use UnzerSDK\test\BasePaymentTest;

class WebhookTest extends BasePaymentTest
{
    /**
     * Verify the constructor of the webhook resource behaves as expected.
     *
     * @test
     */
    public function mandatoryConstructorParametersShouldDefaultToEmptyString(): void
    {
        $webhook = new Webhook();
        $this->assertEquals('', $webhook->getUrl());
        $this->assertEquals('', $webhook->getEvent());
    }

    /**
     * Verify the getters and setters of the webhook resource.
     *
     * @test
     */
    public function gettersAndSettersOfWebhookShouldBehaveAsExpected(): void
    {
        $webhook = new Webhook('https://dev.unzer.com', 'anEventIMadeUp');
        $this->assertEquals('https://dev.unzer.com', $webhook->getUrl());
        $this->assertEquals('anEventIMadeUp', $webhook->getEvent());

        $webhook->setUrl('https://dev.unzer.com');
        $webhook->setEvent('aDifferentEventIMadeUp');
        $this->assertEquals('https://dev.unzer.com', $webhook->getUrl());
        $this->assertEquals('aDifferentEventIMadeUp', $webhook->getEvent());
    }
}
