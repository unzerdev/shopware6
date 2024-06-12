<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the webhook service.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Services;

use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\ResourceServiceInterface;
use UnzerSDK\Resources\Webhook;
use UnzerSDK\Resources\Webhooks;
use UnzerSDK\Services\ResourceService;
use UnzerSDK\Services\WebhookService;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\unit\DummyResource;
use RuntimeException;
use stdClass;

class WebhooksServiceTest extends BasePaymentTest
{
    //<editor-fold desc="General">

    /**
     * Verify setters and getters work properly.
     *
     * @test
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $this->assertSame($unzer, $webhookService->getUnzer());
        $this->assertSame($unzer->getResourceService(), $webhookService->getResourceService());

        $unzer2 = new Unzer('s-priv-1234');
        $resourceService2 = new ResourceService($unzer2);
        $webhookService->setResourceService($resourceService2);
        $this->assertSame($unzer, $webhookService->getUnzer());
        $this->assertNotSame($unzer2->getResourceService(), $webhookService->getResourceService());
        $this->assertSame($resourceService2, $webhookService->getResourceService());

        $webhookService->setUnzer($unzer2);
        $this->assertSame($unzer2, $webhookService->getUnzer());
        $this->assertNotSame($unzer2->getResourceService(), $webhookService->getResourceService());
    }

    //</editor-fold>

    //<editor-fold desc="Webhook">

    /**
     * Verify create webhook calls resource service with webhook object.
     *
     * @test
     */
    public function createWebhookShouldCallResourceServiceWithWebhookObject(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['createResource'])->getMock();
        /** @var ResourceServiceInterface $resourceServiceMock */
        $webhookService->setResourceService($resourceServiceMock);
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('createResource')->with($this->callback(
            static function ($param) use ($unzer) {
                return $param instanceof Webhook &&
                       $param->getUrl() === 'myUrlString' &&
                       $param->getEvent() === 'TestEvent' &&
                       $param->getUnzerObject() === $unzer;
            }
        ));

        $webhookService->createWebhook('myUrlString', 'TestEvent');
    }

    /**
     * Verify fetch webhook calls resource service with the given webhook object.
     *
     * @test
     */
    public function fetchWebhookShouldCallResourceServiceWithTheGivenWebhookObject(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['fetchResource'])->getMock();
        /** @var ResourceServiceInterface $resourceServiceMock */
        $webhookService->setResourceService($resourceServiceMock);
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchResource')->with($this->callback(
            static function ($param) use ($unzer) {
                return $param instanceof Webhook && $param->getUnzerObject() === $unzer;
            }
        ));

        $webhook = new Webhook();
        $webhookService->fetchWebhook($webhook);
    }

    /**
     * Verify fetch webhook calls resource service with a new webhook object with the given id.
     *
     * @test
     */
    public function fetchWebhookShouldCallResourceServiceWithANewWebhookObjectWithTheGivenId(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['fetchResource'])->getMock();
        /** @var ResourceServiceInterface $resourceServiceMock */
        $webhookService->setResourceService($resourceServiceMock);
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchResource')->with($this->callback(
            static function ($param) use ($unzer) {
                return $param instanceof Webhook &&
                       $param->getUnzerObject() === $unzer &&
                       $param->getId() === 'WebhookId';
            }
        ));

        $webhookService->fetchWebhook('WebhookId');
    }

    /**
     * Verify update webhook calls resource service with the given webhook object.
     *
     * @test
     */
    public function updateWebhookShouldCallResourceServiceWithTheGivenWebhookObject(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['updateResource'])->getMock();
        /** @var ResourceServiceInterface $resourceServiceMock */
        $webhookService->setResourceService($resourceServiceMock);
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('updateResource')->with($this->callback(
            static function ($param) use ($unzer) {
                return $param instanceof Webhook &&
                    $param->getUrl() === 'myUrlString' &&
                    $param->getEvent() === 'TestEvent' &&
                    $param->getUnzerObject() === $unzer;
            }
        ));

        $webhook = new Webhook('myUrlString', 'TestEvent');
        $webhookService->updateWebhook($webhook);
    }

    /**
     * Verify delete webhook calls resource service with the given webhook object.
     *
     * @test
     */
    public function deleteWebhookShouldCallResourceServiceWithTheGivenWebhookObject(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['deleteResource'])->getMock();
        /** @var ResourceServiceInterface $resourceServiceMock */
        $webhookService->setResourceService($resourceServiceMock);
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('deleteResource')->with($this->callback(
            static function ($param) {
                return $param instanceof Webhook &&
                    $param->getUrl() === 'myUrlString' &&
                    $param->getEvent() === 'TestEvent';
            }
        ));

        $webhook = new Webhook('myUrlString', 'TestEvent');
        $webhookService->deleteWebhook($webhook);
    }

    /**
     * Verify delete webhook calls resource service with the given webhook object.
     *
     * @test
     */
    public function deleteWebhookShouldCallResourceServiceFetchingAndDeletingTheWebhookWithTheGivenId(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookServiceMock = $this->getMockBuilder(WebhookService::class)->setConstructorArgs([$unzer])
            ->setMethods(['fetchWebhook'])->getMock();
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['fetchResource', 'deleteResource'])->getMock();
        /**
         * @var ResourceServiceInterface $resourceServiceMock
         * @var WebhookService           $webhookServiceMock
         */
        $webhookServiceMock->setResourceService($resourceServiceMock);

        $webhook = new Webhook('WebhookId', 'TestEvent');
        /** @noinspection PhpParamsInspection */
        $webhookServiceMock->expects($this->once())->method('fetchWebhook')->with('WebhookId')
            ->willReturn($webhook);
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('deleteResource')->with($this->callback(
            static function ($param) use ($webhook) {
                return $param === $webhook;
            }
        ));

        $webhookServiceMock->deleteWebhook('WebhookId');
    }

    //</editor-fold>

    //<editor-fold desc="Webhooks">

    /**
     * Verify fetch webhooks calls resource service.
     * In order to be able to verify getWebhookList of the webhooks object is called we needed to return a mocked
     * webhooks object as the fetch method is called.
     *
     * @test
     */
    public function fetchWebhooksShouldCallResourceService(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['fetchResource'])->getMock();
        /** @var ResourceServiceInterface $resourceServiceMock */
        $webhookService->setResourceService($resourceServiceMock);

        $webhooksMock = $this->getMockBuilder(Webhooks::class)->disableOriginalConstructor()
            ->setMethods(['getWebhookList'])->getMock();
        $webhookArray = ['webhook1', 'webhook2'];
        $webhooksMock->expects($this->once())->method('getWebhookList')->willReturn($webhookArray);

        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchResource')->with($this->callback(
            static function ($param) use ($unzer) {
                return $param instanceof Webhooks && $param->getUnzerObject() === $unzer;
            }
        ))->willReturn($webhooksMock);

        $this->assertSame($webhookArray, $webhookService->fetchAllWebhooks());
    }

    /**
     * Verify delete webhooks calls resource service with a new webhooks object.
     *
     * @test
     */
    public function deleteWebhooksShouldCallResourceServiceWithANewWebhooksObject(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['deleteResource'])->getMock();
        /** @var ResourceServiceInterface $resourceServiceMock */
        $webhookService->setResourceService($resourceServiceMock);
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('deleteResource')->with($this->callback(
            static function ($param) use ($unzer) {
                return $param instanceof Webhooks && $param->getUnzerObject() === $unzer;
            }
        ));

        $webhookService->deleteAllWebhooks();
    }

    /**
     * Verify create webhooks calls resource service with webhooks object.
     *
     * @test
     */
    public function createWebhooksShouldCallResourceServiceWithNewWebhooksObject(): void
    {
        $unzer = new Unzer('s-priv-123');
        $webhookService = new WebhookService($unzer);
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['createResource'])->getMock();
        /** @var ResourceServiceInterface $resourceServiceMock */
        $webhookService->setResourceService($resourceServiceMock);

        $webhooksMock = $this->getMockBuilder(Webhooks::class)->setMethods(['getWebhookList'])->getMock();
        $webhookList = ['ListItem1', 'ListItem2'];
        $webhooksMock->expects($this->once())->method('getWebhookList')->willReturn($webhookList);
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('createResource')->with($this->callback(
            static function ($param) use ($unzer) {
                return $param instanceof Webhooks &&
                    $param->getUrl() === 'myUrlString' &&
                    $param->getEventList() === ['TestEvent1', 'TestEvent2'] &&
                    $param->getUnzerObject() === $unzer;
            }
        ))->willReturn($webhooksMock);

        $this->assertEquals(
            $webhookList,
            $webhookService->registerMultipleWebhooks('myUrlString', ['TestEvent1', 'TestEvent2'])
        );
    }

    //</editor-fold>

    //<editor-fold desc="Event handling">

    /**
     * Verify exception is thrown if the retrieveURL is empty.
     *
     * @test
     */
    public function fetchResourceByEventWithEmptyRetrieveUrlShouldThrowException(): void
    {
        // override readInputStreamTo provide custom retrieveURL
        $webhookService = $this->getMockBuilder(WebhookService::class)
            ->setConstructorArgs([new Unzer('s-priv-1234')])->setMethods(['readInputStream'])->getMock();
        $webhookService->expects($this->once())->method('readInputStream')->willReturn('{}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error fetching resource!');

        /** @var WebhookService $webhookService */
        $webhookService->fetchResourceFromEvent();
    }

    /**
     * Verify exception is thrown if the retrieveURL is empty.
     *
     * @test
     */
    public function fetchResourceByEventShouldThrowExceptionIfResourceObjectCanNotBeRetrieved(): void
    {
        // override readInputStreamTo provide custom retrieveURL
        $webhookService = $this->getMockBuilder(WebhookService::class)
            ->setConstructorArgs([new Unzer('s-priv-1234')])->setMethods(['readInputStream'])->getMock();
        $webhookService->expects($this->once())->method('readInputStream')
            ->willReturn('{"retrieveUrl": "/my/url"}');

        // inject resource service mock into webhook service
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()
            ->setMethods(['fetchResourceByUrl'])->getMock();
        $resourceServiceMock->expects($this->once())->method('fetchResourceByUrl')->willReturn(null);
        /**
         * @var ResourceServiceInterface $resourceServiceMock
         * @var WebhookService           $webhookService
         */
        $webhookService->setResourceService($resourceServiceMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error fetching resource!');

        $webhookService->fetchResourceFromEvent();
    }

    /**
     * Verify fetch resource by event.
     *
     * @test
     */
    public function fetchResourceByEventShouldGetResourceServiceWithRetrieveUrl(): void
    {
        // setup received event
        $retrieveUrl            = 'https://test/url';
        $eventData              = new stdClass();
        $eventData->retrieveUrl = $retrieveUrl;
        $receivedJson    = json_encode($eventData);

        // override readInputStream to provide custom retrieveUrl in receivedJson
        $webhookService = $this->getMockBuilder(WebhookService::class)->setConstructorArgs([new Unzer('s-priv-1234')])->setMethods(['readInputStream'])->getMock();
        $webhookService->expects($this->once())->method('readInputStream')->willReturn($receivedJson);

        // inject resource service mock into webhook service to verify fetchResourceByUrl is called with the received url
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['fetchResourceByUrl'])->getMock();
        $dummyResource       = new DummyResource();
        /** @noinspection PhpParamsInspection */
        $resourceServiceMock->expects($this->once())->method('fetchResourceByUrl')->with($retrieveUrl)->willReturn($dummyResource);
        /**
         * @var ResourceServiceInterface $resourceServiceMock
         * @var WebhookService           $webhookService
         */
        $webhookService->setResourceService($resourceServiceMock);

        // trigger test and verify the resource fetched from resourceService is returned
        /** @var WebhookService $webhookService */
        $this->assertSame($dummyResource, $webhookService->fetchResourceFromEvent());
    }

    //</editor-fold>
}
