<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Storefront;

use Exception;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use UnzerPayment6\Components\ApplePay\CertificateManager;
use UnzerPayment6\Components\ApplePay\Exception\MissingCertificateFiles;
use UnzerPayment6\Components\ClientFactory\ClientFactory;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\Unzer;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(defaults={"_routeScope": {"storefront"}})
 */
class UnzerPaymentApplePayController extends StorefrontController
{
    private const MERCHANT_VALIDATION_URL_PARAM = 'merchantValidationUrl';

    /** @var ConfigReaderInterface */
    private $configReader;
    /** @var Filesystem */
    private $filesystem;
    /** @var LoggerInterface */
    private $logger;
    /** @var CertificateManager */
    private $certificateManager;
    /** @var ClientFactory */
    private $clientFactory;
    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(
        ConfigReaderInterface $configReader,
        Filesystem $filesystem,
        LoggerInterface $logger,
        CertificateManager $certificateManager,
        ClientFactory $clientFactory,
        SystemConfigService $systemConfigService
    ) {
        $this->configReader        = $configReader;
        $this->filesystem          = $filesystem;
        $this->logger              = $logger;
        $this->certificateManager  = $certificateManager;
        $this->clientFactory       = $clientFactory;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @Route("/unzer/applePay/validateMerchant", name="frontend.unzer.apple_pay.validate_merchant", methods={"POST"}, defaults={"XmlHttpRequest": true, "csrf_protected": false})
     */
    public function validateMerchant(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $configuration  = $this->configReader->read($salesChannelId, true);

        $displayName = $this->systemConfigService->get('core.basicInformation.shopName', $salesChannelId);

        if (!is_string($displayName)) {
            $displayName = '';
        }

        $applePaySession = new ApplepaySession(
            $configuration->get(ConfigReader::CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFIER),
            $displayName,
            $request->getHost()
        );
        $appleAdapter = new ApplepayAdapter();

        $certificatePath = $this->certificateManager->getMerchantIdentificationCertificatePath($salesChannelId);
        $keyPath         = $this->certificateManager->getMerchantIdentificationKeyPath($salesChannelId);

        if (!$this->filesystem->has($certificatePath) || !$this->filesystem->has($keyPath)) {
            // Try for fallback configuration
            $certificatePath = $this->certificateManager->getMerchantIdentificationCertificatePath('');
            $keyPath         = $this->certificateManager->getMerchantIdentificationKeyPath('');

            if (!$this->filesystem->has($certificatePath) || !$this->filesystem->has($keyPath)) {
                throw new MissingCertificateFiles('Merchant Identification');
            }
        }

        // ApplepayAdapter requires certificate as local files
        $certificateTempPath = tempnam(sys_get_temp_dir(), 'UnzerPayment6');
        $keyTempPath         = tempnam(sys_get_temp_dir(), 'UnzerPayment6');

        if (!$certificateTempPath || !$keyTempPath) {
            throw new RuntimeException('Error on temporary file creation');
        }

        file_put_contents($certificateTempPath, $this->filesystem->read($certificatePath));
        file_put_contents($keyTempPath, $this->filesystem->read($keyPath));

        try {
            $appleAdapter->init($certificateTempPath, $keyTempPath);

            $merchantValidationUrl = urldecode($request->get(self::MERCHANT_VALIDATION_URL_PARAM));

            try {
                $validationResponse = $appleAdapter->validateApplePayMerchant(
                    $merchantValidationUrl,
                    $applePaySession
                );

                return new Response($validationResponse);
            } catch (Exception $e) {
                $this->logger->error('Error in Apple Pay merchant validation', ['exception' => $e]);

                throw $e;
            }
        } finally {
            unlink($keyTempPath);
            unlink($certificateTempPath);
        }
    }

    /**
     * @Route("/unzer/applePay/authorizePayment", name="frontend.unzer.apple_pay.authorize_payment", methods={"POST"}, defaults={"XmlHttpRequest": true, "csrf_protected": false})
     */
    public function authorizePayment(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $client         = $this->clientFactory->createClient($salesChannelId);
        $typeId         = $request->get('id');

        $response = ['transactionStatus' => 'error'];

        try {
            // Charge/Authorize is done in payment handler, return pending to satisfy Apple Pay widget
            $paymentType                   = $client->fetchPaymentType($typeId);
            $response['transactionStatus'] = 'pending';
        } catch (UnzerApiException $e) {
            return new JsonResponse([
                'clientMessage'   => $e->getClientMessage(),
                'merchantMessage' => $e->getMerchantMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            $this->logger->error('Error in Apple Pay authorization call', ['exception' => $e]);

            throw $e;
        }

        return new JsonResponse($response);
    }
}
