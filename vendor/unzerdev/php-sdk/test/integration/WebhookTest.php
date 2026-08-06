<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify Webhook features.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration;

use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\WebhookEvents;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Chargeback;
use UnzerSDK\Resources\Webhook;
use UnzerSDK\test\BaseIntegrationTest;
use function count;
use function in_array;

class WebhookTest extends BaseIntegrationTest
{
    //<editor-fold desc="Webhook tests">
    const CHARGEDBACK_PAYMENT_ID = 's-pay-341196';

    /**
     * Verify Webhook resource can be registered and fetched.
     *
     * @test
     *
     * @dataProvider webhookResourceCanBeRegisteredAndFetchedDP
     * @group CC-1576
     * @param string $event
     */
    public function webhookResourceCanBeRegisteredAndFetched($event): void
    {
        $url = $this->generateUniqueUrl();
        $webhook = $this->unzer->createWebhook($url, $event);
        $this->assertNotNull($webhook->getId());
        $this->assertEquals($event, $webhook->getEvent());

        $fetchedWebhook = $this->unzer->fetchWebhook($webhook->getId());
        $this->assertEquals($webhook->expose(), $fetchedWebhook->expose());
    }

    /**
     * Verify Webhook url can be updated.
     *
     * @test
     */
    public function webhookUrlShouldBeUpdateable(): void
    {
        $url = $this->generateUniqueUrl();
        $webhook = $this->unzer->createWebhook($url, WebhookEvents::ALL);
        $fetchedWebhook = $this->unzer->fetchWebhook($webhook->getId());
        $this->assertEquals(WebhookEvents::ALL, $fetchedWebhook->getEvent());

        $url = $this->generateUniqueUrl();
        $webhook->setUrl($url);
        $this->unzer->updateWebhook($webhook);

        $fetchedWebhook = $this->unzer->fetchWebhook($webhook->getId());
        $this->assertEquals($url, $fetchedWebhook->getUrl());
    }

    /**
     * Verify Webhook event can not be updated.
     *
     * @test
     */
    public function webhookEventShouldNotBeUpdateable(): void
    {
        $webhook = $this->unzer->createWebhook($this->generateUniqueUrl(), WebhookEvents::ALL);
        $fetchedWebhook = $this->unzer->fetchWebhook($webhook->getId());
        $this->assertEquals(WebhookEvents::ALL, $fetchedWebhook->getEvent());

        $webhook->setEvent(WebhookEvents::CUSTOMER);
        $this->unzer->updateWebhook($webhook);

        $fetchedWebhook = $this->unzer->fetchWebhook($webhook->getId());
        $this->assertEquals(WebhookEvents::ALL, $fetchedWebhook->getEvent());
    }

    /**
     * Verify Webhook resource can be deleted.
     *
     * @test
     */
    public function webhookResourceShouldBeDeletable(): void
    {
        $webhook = $this->unzer->createWebhook($this->generateUniqueUrl(), WebhookEvents::ALL);
        $fetchedWebhook = $this->unzer->fetchWebhook($webhook->getId());
        $this->assertEquals(WebhookEvents::ALL, $fetchedWebhook->getEvent());

        $this->assertNull($this->unzer->deleteWebhook($webhook));

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_WEBHOOK_CAN_NOT_BE_FOUND);
        $this->unzer->fetchWebhook($webhook->getId());
    }

    /**
     * Verify webhook create will throw error when the event is already registered for the given URL.
     *
     * @test
     */
    public function webhookCreateShouldThrowErrorWhenEventIsAlreadyRegistered(): void
    {
        $url = $this->generateUniqueUrl();
        $this->unzer->createWebhook($url, WebhookEvents::ALL);

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_WEBHOOK_EVENT_ALREADY_REGISTERED);
        $this->unzer->createWebhook($url, WebhookEvents::ALL);
    }

    //</editor-fold>

    //<editor-fold desc="Webhooks test">

    /**
     * Verify fetching all registered webhooks will return an array of webhooks.
     *
     * @test
     * @group CC-1576
     */
    public function fetchWebhooksShouldReturnArrayOfRegisteredWebhooks(): void
    {
        // --- Prepare --> remove all existing webhooks
        // start workaround - avoid error deleting non existing webhooks
        $this->unzer->createWebhook($this->generateUniqueUrl(), WebhookEvents::CUSTOMER);
        // end workaround - avoid error deleting non existing webhooks

        $this->unzer->deleteAllWebhooks();
        $webhooks = $this->unzer->fetchAllWebhooks();
        $this->assertCount(0, $webhooks);

        // --- Create some test webhooks ---
        $webhook1 = $this->unzer->createWebhook($this->generateUniqueUrl(), WebhookEvents::CUSTOMER);
        $webhook2 = $this->unzer->createWebhook($this->generateUniqueUrl(), WebhookEvents::CHARGE);
        $webhook3 = $this->unzer->createWebhook($this->generateUniqueUrl(), WebhookEvents::AUTHORIZE);
        $webhook4 = $this->unzer->createWebhook($this->generateUniqueUrl(), WebhookEvents::PREAUTHORIZE);

        // --- Verify webhooks have been registered ---
        $fetchedWebhooks = $this->unzer->fetchAllWebhooks();
        $this->assertCount(4, $fetchedWebhooks);

        $this->assertTrue($this->arrayContainsWebhook($fetchedWebhooks, $webhook1));
        $this->assertTrue($this->arrayContainsWebhook($fetchedWebhooks, $webhook2));
        $this->assertTrue($this->arrayContainsWebhook($fetchedWebhooks, $webhook3));
        $this->assertTrue($this->arrayContainsWebhook($fetchedWebhooks, $webhook4));
    }

    /**
     * Verify all webhooks can be removed at once.
     *
     * @test
     *
     * @depends webhookResourceCanBeRegisteredAndFetched
     */
    public function allWebhooksShouldBeRemovableAtOnce(): void
    {
        // --- Verify webhooks have been registered ---
        $webhooks = $this->unzer->fetchAllWebhooks();
        $this->assertGreaterThan(0, count($webhooks));

        // --- Verify all webhooks can be removed at once ---
        $this->unzer->deleteAllWebhooks();
        $webhooks = $this->unzer->fetchAllWebhooks();
        $this->assertCount(0, $webhooks);
    }

    /**
     * Verify setting multiple events at once.
     *
     * @test
     *
     * @depends allWebhooksShouldBeRemovableAtOnce
     */
    public function bulkSettingWebhookEventsShouldBePossible(): void
    {
        $webhookEvents = [WebhookEvents::AUTHORIZE, WebhookEvents::CHARGE, WebhookEvents::SHIPMENT, WebhookEvents::PREAUTHORIZE];
        $url = $this->generateUniqueUrl();
        $registeredWebhooks = $this->unzer->registerMultipleWebhooks($url, $webhookEvents);

        // check whether the webhooks have the correct url
        $registeredEvents = [];
        foreach ($registeredWebhooks as $webhook) {
            /** @var Webhook $webhook */
            if (in_array($webhook->getEvent(), $webhookEvents, true)) {
                $this->assertEquals($url, $webhook->getUrl());
            }
            $registeredEvents[] = $webhook->getEvent();
        }

        // check whether all of the webhookEvents exist
        sort($webhookEvents);
        sort($registeredEvents);
        $this->assertEquals($webhookEvents, $registeredEvents);
    }

    /**
     * Verify setting one event with bulk setting.
     *
     * @test
     */
    public function bulkSettingOnlyOneWebhookShouldBePossible(): void
    {
        // remove all existing webhooks a avoid errors here
        $this->unzer->deleteAllWebhooks();

        $url = $this->generateUniqueUrl();
        $registeredWebhooks = $this->unzer->registerMultipleWebhooks($url, [WebhookEvents::AUTHORIZE]);

        $this->assertCount(1, $registeredWebhooks);

        /** @var Webhook $webhook */
        $webhook = $registeredWebhooks[0];

        $this->assertEquals(WebhookEvents::AUTHORIZE, $webhook->getEvent());
        $this->assertEquals($url, $webhook->getUrl());
    }

    /**
     * @test
     */
    public function testFetchResourceFromEvent(): void
    {
        $webhookNotification = [
            'event' => 'chargebacks',
            'publicKey' => 's-pub-xyz',
            'retrieveUrl' => 'https://sbx-api.unzer.com/v1/payments/' . self::CHARGEDBACK_PAYMENT_ID . '/charges/s-chg-1/chargebacks/s-cbk-1',
            'paymentId' => self::CHARGEDBACK_PAYMENT_ID
        ];

        // when
        $chargeback = $this->unzer->fetchResourceFromEvent(json_encode($webhookNotification));

        // then
        $this->assertInstanceOf(Chargeback::class, $chargeback);
        $this->assertNotNull($chargeback);
        $this->assertEquals('s-cbk-1', $chargeback->getId());
    }

    //</editor-fold>

    //<editor-fold desc="Helpers">

    /**
     * Returns a unique url based on the current timestamp.
     *
     * @return string
     */
    private function generateUniqueUrl(): string
    {
        return 'https://www.unzer.com?test=' . str_replace([' ', '.'], '', microtime());
    }

    /**
     * Returns true if the given Webhook exists in the given array.
     *
     * @param         $webhooksArray
     * @param Webhook $webhook
     *
     * @return bool
     */
    private function arrayContainsWebhook($webhooksArray, Webhook $webhook): bool
    {
        $arrayContainsWebhook = false;
        foreach ($webhooksArray as $webhookFromArray) {
            /** @var Webhook $webhookFromArray */
            if ($webhookFromArray->expose() === $webhook->expose()) {
                $arrayContainsWebhook = true;
                break;
            }
        }
        return $arrayContainsWebhook;
    }

    //</editor-fold>

    //<editor-fold desc="Data Providers">

    /**
     * Returns a test data set.
     *
     * @return array
     */
    public function webhookResourceCanBeRegisteredAndFetchedDP(): array
    {
        $testData = [];
        foreach (WebhookEvents::ALLOWED_WEBHOOKS as $event) {
            $testData[$event] = [$event];
        }

        return $testData;
    }

    //</editor-fold>
}
