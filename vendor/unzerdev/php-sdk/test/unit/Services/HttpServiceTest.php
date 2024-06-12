<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines unit tests to verify functionality of the HttpService.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Services;

use UnzerSDK\Adapter\CurlAdapter;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\DebugHandlerInterface;
use UnzerSDK\Services\EnvironmentService;
use UnzerSDK\Services\HttpService;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\unit\DummyResource;
use RuntimeException;

use function array_key_exists;

use const PHP_VERSION;

class HttpServiceTest extends BasePaymentTest
{
    /**
     * Verify getAdapter will return a CurlAdapter if none has been set.
     *
     * @test
     */
    public function getAdapterShouldReturnDefaultAdapterIfNonHasBeenSet(): void
    {
        $httpService = new HttpService();
        $this->assertInstanceOf(CurlAdapter::class, $httpService->getAdapter());
    }

    /**
     * Verify getAdapter will return custom adapter if it has been set.
     *
     * @test
     */
    public function getAdapterShouldReturnCustomAdapterIfItHasBeenSet(): void
    {
        $dummyAdapter = new DummyAdapter();
        $httpService = (new HttpService())->setHttpAdapter($dummyAdapter);
        $this->assertSame($dummyAdapter, $httpService->getAdapter());
    }

    /**
     * Verify an environment service can be injected.
     *
     * @test
     */
    public function environmentServiceShouldBeInjectable(): void
    {
        $envService = new EnvironmentService();
        $httpService = new HttpService();
        $this->assertNotSame($envService, $httpService->getEnvironmentService());
        $httpService->setEnvironmentService($envService);
        $this->assertSame($envService, $httpService->getEnvironmentService());
    }

    /**
     * Verify send will throw exception if resource is null.
     *
     * @test
     */
    public function sendShouldThrowExceptionIfResourceIsNotSet(): void
    {
        $httpService = new HttpService();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transfer object is empty!');
        $httpService->send();
    }

    /**
     * Verify send calls methods to setup and send request.
     *
     * @test
     */
    public function sendShouldInitAndSendRequest(): void
    {
        $httpServiceMock = $this->getMockBuilder(HttpService::class)->setMethods(['getAdapter'])->getMock();

        $adapterMock = $this->getMockBuilder(CurlAdapter::class)->setMethods(
            ['init', 'setUserAgent', 'setHeaders', 'execute', 'getResponseCode', 'close']
        )->getMock();

        $resource = (new DummyResource())->setParentResource(new Unzer('s-priv-MyTestKey'));
        /** @noinspection PhpParamsInspection */
        $adapterMock->expects($this->once())->method('init')->with(
            $this->callback(
                static function ($url) {
                    return str_replace(['dev-api', 'stg-api'], 'sbx-api', $url) === 'https://sbx-api.unzer.com/v1/my/uri/123';
                }
            ),
            '{"dummyResource": "JsonSerialized"}',
            'GET'
        );
        /** @noinspection PhpParamsInspection */
        $adapterMock->expects($this->once())->method('setUserAgent')->with('UnzerPHP');
        $headers = [
            'Authorization' => 'Basic cy1wcml2LU15VGVzdEtleTo=',
            'Content-Type'  => 'application/json',
            'SDK-VERSION'   => Unzer::SDK_VERSION,
            'SDK-TYPE'   => Unzer::SDK_TYPE,
            'PHP-VERSION'   => PHP_VERSION
        ];
        $adapterMock->expects($this->once())->method('setHeaders')->with($headers);
        $adapterMock->expects($this->once())->method('execute')->willReturn('myResponseString');
        $adapterMock->expects($this->once())->method('getResponseCode')->willReturn('399');
        $adapterMock->expects($this->once())->method('close');

        $httpServiceMock->method('getAdapter')->willReturn($adapterMock);

        /** @var HttpService $httpServiceMock*/
        $response = $httpServiceMock->send('/my/uri/123', $resource);

        $this->assertEquals('myResponseString', $response);
    }

    /**
     * Verify 'Accept-Language' header only set when a locale is defined in the Unzer object.
     *
     * @test
     *
     * @dataProvider languageShouldOnlyBeSetIfSpecificallyDefinedDP
     *
     * @param $locale
     */
    public function languageShouldOnlyBeSetIfSpecificallyDefined($locale): void
    {
        $httpServiceMock = $this->getMockBuilder(HttpService::class)->setMethods(['getAdapter'])->getMock();
        $adapterMock = $this->getMockBuilder(CurlAdapter::class)->setMethods(['setHeaders', 'execute'])->getMock();
        $httpServiceMock->method('getAdapter')->willReturn($adapterMock);

        $resource = (new DummyResource())->setParentResource(new Unzer('s-priv-MyTestKey', $locale));

        /** @noinspection PhpParamsInspection */
        $adapterMock->expects($this->once())->method('setHeaders')->with(
            $this->callback(
                static function ($headers) use ($locale) {
                    return $locale === ($headers['Accept-Language'] ?? null);
                }
            )
        );
        $adapterMock->method('execute')->willReturn('myResponseString');

        /** @var HttpService $httpServiceMock*/
        $httpServiceMock->send('/my/uri/123', $resource);
    }

    /**
     * Verify 'CLIENTIP' header only set when a clientIp is defined in the Unzer object.
     *
     * @test
     *
     * @dataProvider clientIpHeaderShouldBeSetProperlyDP
     *
     * @param       $clientIp
     * @param mixed $isHeaderExpected
     */
    public function clientIpHeaderShouldBeSetProperly($clientIp, $isHeaderExpected): void
    {
        $unzer = new Unzer('s-priv-MyTestKey');
        $unzer->setClientIp($clientIp);

        $composeHttpHeaders = $unzer->getHttpService()->composeHttpHeaders($unzer);
        $this->assertEquals($isHeaderExpected, isset($composeHttpHeaders['CLIENTIP']));
        if ($isHeaderExpected) {
            $this->assertEquals($clientIp, $composeHttpHeaders['CLIENTIP']);
        }
    }

    /**
     * Verify debugLog logs to debug handler if debug mode and a handler are set.
     *
     * @test
     */
    public function sendShouldLogDebugMessagesIfDebugModeAndHandlerAreSet(): void
    {
        $httpServiceMock = $this->getMockBuilder(HttpService::class)->setMethods(['getAdapter'])->getMock();

        $adapterMock = $this->getMockBuilder(CurlAdapter::class)->setMethods(['init', 'setUserAgent', 'setHeaders', 'execute', 'getResponseCode', 'close'])->getMock();
        $adapterMock->method('execute')->willReturn('{"response":"myResponseString"}');
        $adapterMock->method('getResponseCode')->willReturnOnConsecutiveCalls('200', '201');
        $httpServiceMock->method('getAdapter')->willReturn($adapterMock);

        $loggerMock = $this->getMockBuilder(DummyDebugHandler::class)->setMethods(['log'])->getMock();
        $loggerMock->expects($this->exactly(7))->method('log')->withConsecutive(
            [ $this->callback(
                static function ($string) {
                    return str_replace(['dev-api', 'stg-api'], 'sbx-api', $string) === '(' . (getmypid()) . ') GET: https://sbx-api.unzer.com/v1/my/uri/123';
                }
            )
            ],
            [ $this->callback(
                static function ($string) {
                    $matches = [];
                    preg_match('/^(?:\([\d]*\) Headers: )({.*})/', $string, $matches);
                    $elements = json_decode($matches[1], true);
                    return array_key_exists('Authorization', $elements) && array_key_exists('Content-Type', $elements) &&
                           array_key_exists('SDK-TYPE', $elements) && array_key_exists('SDK-VERSION', $elements);
                }
            )
            ],
            ['(' . (getmypid()) . ') Response: (200) {"response":"myResponseString"}'],
            [ $this->callback(
                static function ($string) {
                    return str_replace(['dev-api', 'stg-api'], 'sbx-api', $string) === '(' . (getmypid()) . ') POST: https://sbx-api.unzer.com/v1/my/uri/123';
                }
            )
            ],
            [ $this->callback(
                static function ($string) {
                    $matches = [];
                    preg_match('/^(?:\([\d]*\) Headers: )({.*})/', $string, $matches);
                    $elements = json_decode($matches[1], true);
                    return array_key_exists('Authorization', $elements) && array_key_exists('Content-Type', $elements) &&
                        array_key_exists('SDK-TYPE', $elements) && array_key_exists('SDK-VERSION', $elements);
                }
            )
            ],
            ['(' . (getmypid()) . ') Request: {"dummyResource": "JsonSerialized"}'],
            ['(' . (getmypid()) . ') Response: (201) {"response":"myResponseString"}']
        );

        /** @var DebugHandlerInterface $loggerMock */
        $unzer = (new Unzer('s-priv-MyTestKey'))->setDebugMode(true)->setDebugHandler($loggerMock);
        $resource  = (new DummyResource())->setParentResource($unzer);

        /** @var HttpService $httpServiceMock*/
        $response = $httpServiceMock->send('/my/uri/123', $resource);
        $this->assertEquals('{"response":"myResponseString"}', $response);

        $response = $httpServiceMock->send('/my/uri/123', $resource, HttpAdapterInterface::REQUEST_POST);
        $this->assertEquals('{"response":"myResponseString"}', $response);
    }

    /**
     * Verify handleErrors will throw Exception if response string is null.
     *
     * @test
     */
    public function handleErrorsShouldThrowExceptionIfResponseIsEmpty(): void
    {
        $httpServiceMock = $this->getMockBuilder(HttpService::class)->setMethods(['getAdapter'])->getMock();

        $adapterMock = $this->getMockBuilder(CurlAdapter::class)->setMethods(
            ['init', 'setUserAgent', 'setHeaders', 'execute', 'getResponseCode', 'close']
        )->getMock();
        $adapterMock->method('execute')->willReturn(null);
        $httpServiceMock->method('getAdapter')->willReturn($adapterMock);

        $resource  = (new DummyResource())->setParentResource(new Unzer('s-priv-MyTestKey'));

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionMessage('The Request returned a null response!');
        $this->expectExceptionCode('No error code provided');

        /** @var HttpService $httpServiceMock*/
        $httpServiceMock->send('/my/uri/123', $resource);
    }

    /**
     * Verify handleErrors will throw Exception if responseCode is greaterOrEqual to 400 or is not a number.
     *
     * @test
     *
     * @dataProvider responseCodeProvider
     *
     * @param string $responseCode
     */
    public function handleErrorsShouldThrowExceptionIfResponseCodeIsGoE400($responseCode): void
    {
        $httpServiceMock = $this->getMockBuilder(HttpService::class)->setMethods(['getAdapter'])->getMock();

        $adapterMock = $this->getMockBuilder(CurlAdapter::class)->setMethods(
            ['init', 'setUserAgent', 'setHeaders', 'execute', 'getResponseCode', 'close']
        )->getMock();
        $adapterMock->method('getResponseCode')->willReturn($responseCode);
        $adapterMock->method('execute')->willReturn('{"response" : "myResponseString"}');
        $httpServiceMock->method('getAdapter')->willReturn($adapterMock);

        $resource  = (new DummyResource())->setParentResource(new Unzer('s-priv-MyTestKey'));

        $this->expectException(UnzerApiException::class);
        $this->expectExceptionMessage('The payment api returned an error!');
        $this->expectExceptionCode('No error code provided');

        /** @var HttpService $httpServiceMock*/
        $httpServiceMock->send('/my/uri/123', $resource);
    }

    /**
     * Verify handleErrors will throw Exception if response contains errors field.
     *
     * @test
     */
    public function handleErrorsShouldThrowExceptionIfResponseContainsErrorField(): void
    {
        $httpServiceMock = $this->getMockBuilder(HttpService::class)->setMethods(['getAdapter'])->getMock();
        $adapterMock = $this->getMockBuilder(CurlAdapter::class)->setMethods(
            ['init', 'setUserAgent', 'setHeaders', 'execute', 'getResponseCode', 'close']
        )->getMock();

        $firstResponse = '{"errors": [{}]}';
        $secondResponse = '{"errors": [{"merchantMessage": "This is an error message for the merchant!"}]}';
        $thirdResponse = '{"errors": [{"customerMessage": "This is an error message for the customer!"}]}';
        $fourthResponse = '{"errors": [{"code": "This is the error code!"}]}';
        $fifthResponse = '{"errors": [{"code": "This is the error code!"}], "id": "s-err-1234"}';
        $sixthResponse = '{"errors": [{"code": "This is the error code!"}], "id": "s-rre-1234"}';

        $adapterMock->method('execute')->willReturnOnConsecutiveCalls($firstResponse, $secondResponse, $thirdResponse, $fourthResponse, $fifthResponse, $sixthResponse);
        $httpServiceMock->method('getAdapter')->willReturn($adapterMock);

        $resource  = (new DummyResource())->setParentResource(new Unzer('s-priv-MyTestKey'));

        /** @var HttpService $httpServiceMock*/
        try {
            $httpServiceMock->send('/my/uri/123', $resource);
            $this->assertTrue(false, 'The first exception should have been thrown!');
        } catch (UnzerApiException $e) {
            $this->assertEquals('The payment api returned an error!', $e->getMerchantMessage());
            $this->assertEquals('The payment api returned an error!', $e->getClientMessage());
            $this->assertEquals('No error code provided', $e->getCode());
            $this->assertEquals('No error id provided', $e->getErrorId());
        }

        try {
            $httpServiceMock->send('/my/uri/123', $resource);
            $this->assertTrue(false, 'The second exception should have been thrown!');
        } catch (UnzerApiException $e) {
            $this->assertEquals('This is an error message for the merchant!', $e->getMerchantMessage());
            $this->assertEquals('The payment api returned an error!', $e->getClientMessage());
            $this->assertEquals('No error code provided', $e->getCode());
            $this->assertEquals('No error id provided', $e->getErrorId());
        }

        try {
            $httpServiceMock->send('/my/uri/123', $resource);
            $this->assertTrue(false, 'The third exception should have been thrown!');
        } catch (UnzerApiException $e) {
            $this->assertEquals('The payment api returned an error!', $e->getMerchantMessage());
            $this->assertEquals('This is an error message for the customer!', $e->getClientMessage());
            $this->assertEquals('No error code provided', $e->getCode());
            $this->assertEquals('No error id provided', $e->getErrorId());
        }

        try {
            $httpServiceMock->send('/my/uri/123', $resource);
            $this->assertTrue(false, 'The fourth exception should have been thrown!');
        } catch (UnzerApiException $e) {
            $this->assertEquals('The payment api returned an error!', $e->getMerchantMessage());
            $this->assertEquals('The payment api returned an error!', $e->getClientMessage());
            $this->assertEquals('This is the error code!', $e->getCode());
            $this->assertEquals('No error id provided', $e->getErrorId());
        }

        try {
            $httpServiceMock->send('/my/uri/123', $resource);
            $this->assertTrue(false, 'The fifth exception should have been thrown!');
        } catch (UnzerApiException $e) {
            $this->assertEquals('The payment api returned an error!', $e->getMerchantMessage());
            $this->assertEquals('The payment api returned an error!', $e->getClientMessage());
            $this->assertEquals('This is the error code!', $e->getCode());
            $this->assertEquals('s-err-1234', $e->getErrorId());
        }

        try {
            $httpServiceMock->send('/my/uri/123', $resource);
            $this->assertTrue(false, 'The sixth exception should have been thrown!');
        } catch (UnzerApiException $e) {
            $this->assertEquals('The payment api returned an error!', $e->getMerchantMessage());
            $this->assertEquals('The payment api returned an error!', $e->getClientMessage());
            $this->assertEquals('This is the error code!', $e->getCode());
            $this->assertEquals('No error id provided', $e->getErrorId());
        }
    }

    /**
     * Verify API environment switches accordingly depending on environment variable and keypair.
     *
     * @test
     *
     * @dataProvider environmentUrlSwitchesWithEnvironmentVariableDP
     *
     * @param        $environment
     * @param        $apiUrl
     * @param string $key
     */
    public function environmentUrlSwitchesWithEnvironmentVariable($environment, $apiUrl, string $key): void
    {
        $adapterMock = $this->getMockBuilder(CurlAdapter::class)->setMethods(['init', 'setUserAgent', 'setHeaders', 'execute', 'getResponseCode', 'close'])->getMock();
        /** @noinspection PhpParamsInspection */
        $adapterMock->expects($this->once())->method('init')->with($apiUrl, self::anything(), self::anything());
        $resource = (new DummyResource())->setParentResource(new Unzer($key));
        $adapterMock->method('execute')->willReturn('myResponseString');
        $adapterMock->method('getResponseCode')->willReturn('42');

        $envSrvMock = $this->getMockBuilder(EnvironmentService::class)->setMethods(['getPapiEnvironment'])->getMock();
        $envSrvMock->method('getPapiEnvironment')->willReturn($environment);

        /**
         * @var CurlAdapter        $adapterMock
         * @var EnvironmentService $envSrvMock
         */
        $httpService = (new HttpService())->setHttpAdapter($adapterMock)->setEnvironmentService($envSrvMock);

        /** @var HttpService $httpServiceMock*/
        $response = $httpService->send('', $resource);

        $this->assertEquals('myResponseString', $response);
    }

    //<editor-fold desc="DataProviders">

    /**
     * Data provider for handleErrorsShouldThrowExceptionIfResponseCodeIsGoE400.
     *
     * @return array
     */
    public function responseCodeProvider(): array
    {
        return [
            '400' => ['400'],
            '401' => ['401'],
            '404' => ['404'],
            '500' => ['500'],
            '600' => ['600'],
            '1000' => ['1000'],
            'Response code not a number' => ['myResponseCode']
        ];
    }

    /**
     * Returns test data for method public function languageShouldOnlyBeSetIfSpecificallyDefined.
     */
    public function languageShouldOnlyBeSetIfSpecificallyDefinedDP(): array
    {
        return [
            'de-DE' => ['de-DE'],
            'en-US' => ['en-US'],
            'null' => [null]
        ];
    }

    /**
     * Returns test data for method public function languageShouldOnlyBeSetIfSpecificallyDefined.
     */
    public function clientIpHeaderShouldBeSetProperlyDP(): array
    {
        return [
            'valid ipv4' => ['111.222.333.444', true],
            'valid ipv6' => ['684D:1111:222:3333:4444:5555:6:7', true],
            'valid ipv6 (dual)' => ['2001:db8:3333:4444:5555:6666:1.2.3.4', true],
            'empty string' => ['', false],
            'null' => [null, false]
        ];
    }

    /**
     * @return array
     */
    public function environmentUrlSwitchesWithEnvironmentVariableDP(): array
    {
        $devUrl = 'https://dev-api.unzer.com/v1';
        $stgUrl = 'https://stg-api.unzer.com/v1';
        $sbxUrl = 'https://sbx-api.unzer.com/v1';
        $prodUrl = 'https://api.unzer.com/v1';

        $prodKey = 'p-priv-MyTestKey';
        $sbxKey = 's-priv-MyTestKey';

        return [
            'Dev with production key' => [EnvironmentService::ENV_VAR_VALUE_DEVELOPMENT_ENVIRONMENT, $prodUrl, $prodKey],
            'Prod with production key' => [EnvironmentService::ENV_VAR_VALUE_PROD_ENVIRONMENT, $prodUrl, $prodKey],
            'Stg with production key' => [EnvironmentService::ENV_VAR_VALUE_STAGING_ENVIRONMENT, $prodUrl, $prodKey],
            'something else with production key' => ['something else', $prodUrl, $prodKey],
            'undefined with production key' => ['', $prodUrl, $prodKey],
            'Dev with sandbox key' => [EnvironmentService::ENV_VAR_VALUE_DEVELOPMENT_ENVIRONMENT, $devUrl, $sbxKey],
            'Prod with sandbox key' => [EnvironmentService::ENV_VAR_VALUE_PROD_ENVIRONMENT, $sbxUrl, $sbxKey],
            'Stg with sandbox key' => [EnvironmentService::ENV_VAR_VALUE_STAGING_ENVIRONMENT, $stgUrl, $sbxKey],
            'something else with sandbox key' => ['something else', $sbxUrl, $sbxKey],
            'undefined with sandbox key' => ['', $sbxUrl, $sbxKey],
        ];
    }

    //</editor-fold>
}
