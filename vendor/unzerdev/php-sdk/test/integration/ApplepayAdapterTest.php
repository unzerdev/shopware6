<?php
/*
 *  Test class for applepay adapter
 *
 *  @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\integration;

use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Exceptions\ApplepayMerchantValidationException;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;
use UnzerSDK\test\BaseIntegrationTest;
use UnzerSDK\test\Helper\TestEnvironmentService;

class ApplepayAdapterTest extends BaseIntegrationTest
{
    /** @var string $appleCaCertificatePath Path to ca Certificate file. */
    protected $appleCaCertificatePath;
    /** @var string $merchantValidationUrl merchant validation url for testing. */
    private $merchantValidationUrl;
    /** @var string $applepayCombinedCertPath Path to combined certificate file. */
    private $applepayCombinedCertPath;
    /** @var string $applepayCertPath Path to merchant ID certificate file. */
    private $applepayCertPath;
    /** @var string $applepayKeyPath Path to merchant ID key file. */
    private $applepayKeyPath;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->merchantValidationUrl = 'https://apple-pay-gateway-cert.apple.com/paymentservices/startSession';

        $appleMerchantIdPath = TestEnvironmentService::getAppleMerchantIdPath();

        $this->applepayCertPath = $this->createFilePath($appleMerchantIdPath, 'merchant_id.pem');
        $this->applepayKeyPath = $this->createFilePath($appleMerchantIdPath, 'merchant_id.key');
        $this->applepayCombinedCertPath = $this->createFilePath($appleMerchantIdPath, 'apple-pay-cert.pem');
        $this->appleCaCertificatePath = TestEnvironmentService::getAppleCaCertificatePath();
    }

    /**
     * Test merchant validation request.
     *
     * @test
     *
     * @throws ApplepayMerchantValidationException
     */
    public function verifyMerchantValidationRequest(): void
    {
        $applepaySession = $this->createApplepaySession();
        $appleAdapter = new ApplepayAdapter();
        $appleAdapter->init($this->applepayCertPath, $this->applepayKeyPath, $this->appleCaCertificatePath);

        $validationResponse = $appleAdapter->validateApplePayMerchant(
            $this->merchantValidationUrl,
            $applepaySession
        );

        $this->assertNotNull($validationResponse);
    }

    /**
     * @return ApplepaySession
     */
    private function createApplepaySession(): ApplepaySession
    {
        return new ApplepaySession('merchantIdentifier', 'displayName', 'domainName');
    }

    /**
     * Test merchant validation request without ca certificate.
     *
     * @test
     *
     * @throws ApplepayMerchantValidationException
     */
    public function merchantValidationWorksWithApplepayCertOnly(): void
    {
        $applepaySession = $this->createApplepaySession();
        $appleAdapter = new ApplepayAdapter();
        $appleAdapter->init($this->applepayCombinedCertPath);

        $validationResponse = $appleAdapter->validateApplePayMerchant(
            $this->merchantValidationUrl,
            $applepaySession
        );

        $this->assertNotNull($validationResponse);
    }

    /**
     * Test merchant validation request without ca certificate.
     *
     * @test
     *
     * @throws ApplepayMerchantValidationException
     */
    public function merchantValidationWorksWithCertAndKey(): void
    {
        $applepaySession = $this->createApplepaySession();
        $appleAdapter = new ApplepayAdapter();
        $appleAdapter->init($this->applepayCertPath, $this->applepayKeyPath);

        $validationResponse = $appleAdapter->validateApplePayMerchant(
            $this->merchantValidationUrl,
            $applepaySession
        );

        $this->assertNotNull($validationResponse);
    }

    /**
     * Test merchant validation request without key and only the merchant id certificate should throw exception.
     *
     * @test
     *
     * @throws ApplepayMerchantValidationException
     */
    public function missingKeyShouldThrowException(): void
    {
        $applepaySession = $this->createApplepaySession();
        $appleAdapter = new ApplepayAdapter();
        $appleAdapter->init($this->applepayCertPath);

        $this->expectException(ApplepayMerchantValidationException::class);
        $appleAdapter->validateApplePayMerchant(
            $this->merchantValidationUrl,
            $applepaySession
        );
    }

    /**
     * Test merchant validation request without init() call should throw exception.
     *
     * @test
     *
     * @throws ApplepayMerchantValidationException
     */
    public function missingInitCallThrowsException(): void
    {
        $applepaySession = $this->createApplepaySession();
        $appleAdapter = new ApplepayAdapter();

        $this->expectException(ApplepayMerchantValidationException::class);
        $this->expectExceptionMessage('No curl adapter initiated yet. Make sure to cal init() function before.');

        $appleAdapter->validateApplePayMerchant(
            $this->merchantValidationUrl,
            $applepaySession
        );
    }

    /**
     * Merchant validation call should throw Exception if domain of Validation url is invalid.
     *
     * @test
     *
     */
    public function merchantValidationThrowsErrorForInvalidDomain(): void
    {
        $applepaySession = $this->createApplepaySession();
        $appleAdapter = new ApplepayAdapter();
        $appleAdapter->init($this->applepayCombinedCertPath);

        $this->expectException(ApplepayMerchantValidationException::class);
        $this->expectExceptionMessage('Invalid URL used for merchantValidation request.');

        $appleAdapter->validateApplePayMerchant(
            'https://invalid.domain.com/some/path',
            $applepaySession
        );
    }

    /**
     * test merchant validation request without ca certificate.
     *
     * @dataProvider domainShouldBeValidatedCorrectlyDP
     *
     * @test
     *
     * @param mixed $validationUrl
     * @param mixed $expectedResult
     *
     */
    public function domainShouldBeValidatedCorrectly($validationUrl, $expectedResult): void
    {
        $appleAdapter = new ApplepayAdapter();

        $domainValidation = $appleAdapter->validMerchantValidationDomain($validationUrl);
        $this->assertEquals($expectedResult, $domainValidation);
    }

    /** Provides different urls to test domain validation.
     *
     * @return array[]
     */
    public function domainShouldBeValidatedCorrectlyDP(): array
    {
        return [
            'invalid: example.domain.com' => ['https://example.domain.com', false],
            'valid: https://apple-pay-gateway.apple.com/some/path' => ['https://apple-pay-gateway.apple.com/some/path', true],
            'valid: https://cn-apple-pay-gateway.apple.com' => ['https://cn-apple-pay-gateway.apple.com', true],
            'invalid: apple-pay-gateway-nc-pod1.apple.com' => ['apple-pay-gateway-nc-pod1.apple.com', false],
            'invalid: (empty)' => ['', false],
        ];
    }

    /**
     * @param string $appleMerchantIdPath
     * @param string $merchantIdFile
     *
     * @return string
     */
    protected function createFilePath(string $appleMerchantIdPath, string $merchantIdFile): string
    {
        $separator = DIRECTORY_SEPARATOR;
        return rtrim($appleMerchantIdPath, $separator) . $separator . $merchantIdFile;
    }
}
