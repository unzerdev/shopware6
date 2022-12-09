<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use UnzerPayment6\Components\ApplePay\Exception\MissingCertificateFiles;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\Resource\ApplePayCertificate;
use UnzerPayment6\Components\Resource\ApplePayPrivateKey;

/**
 * @RouteScope(scopes={"api"})
 */
class UnzerPaymentApplePayController extends AbstractController
{
    public const APPLE_PAY_CERTIFICATE_PATH                   = 'unzer_payment6_apple_pay_certificates';
    public const MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME = 'merchant-identification-certificate.pem';
    public const MERCHANT_IDENTIFICATION_KEY_FILENAME         = 'merchant-identification-privatekey.key';

    private const PAYMENT_PROCESSING_CERTIFICATE_PARAMETER      = 'paymentProcessingCertificate';
    private const PAYMENT_PROCESSING_KEY_PARAMETER              = 'paymentProcessingKey';
    private const PAYMENT_PROCESSING_CERTIFICATE_PARAMETER_KEYS = [
        self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER,
        self::PAYMENT_PROCESSING_KEY_PARAMETER,
    ];
    private const MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER  = 'merchantIdentificationCertificate';
    private const MERCHANT_IDENTIFICATION_KEY_PARAMETER          = 'merchantIdentificationKey';
    private const MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETERS = [
        self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER,
        self::MERCHANT_IDENTIFICATION_KEY_PARAMETER,
    ];

    private ClientFactoryInterface $clientFactory;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private FilesystemInterface $filesystem;
    private ConfigReaderInterface $configReader;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
        FilesystemInterface $filesystem,
        ConfigReaderInterface $configReader
    ) {
        $this->clientFactory       = $clientFactory;
        $this->logger              = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->filesystem          = $filesystem;
        $this->configReader        = $configReader;
    }

    /**
     * @Route("/api/_action/unzer-payment/apple-pay/certificates/{salesChannelId}", name="api.action.unzer.apple-pay.update-certificates", methods={"POST"}, defaults={"salesChannelId": null, "_route_scope": {"api"}})
     * @Route("/api/v{version}/_action/unzer-payment/apple-pay/certificates/{salesChannelId}", name="api.action.unzer.apple-pay.update-certificates.version", methods={"POST"}, defaults={"salesChannelId": null, "_route_scope": {"api"}})
     */
    public function updateApplePayCertificates(RequestDataBag $dataBag): JsonResponse
    {
        $salesChannelId = $dataBag->get('salesChannelId');
        $client         = $this->clientFactory->createClient($salesChannelId);

        if ($dataBag->has(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER) && $dataBag->has(self::PAYMENT_PROCESSING_KEY_PARAMETER)) {
            // TODO: Verify certificates (if openssl is present)

            $privateKeyResource = new ApplePayPrivateKey();
            $privateKeyResource->setCertificate($dataBag->get(self::PAYMENT_PROCESSING_KEY_PARAMETER));

            $client->getResourceService()->createResource($privateKeyResource);
            $privateKeyId = $privateKeyResource->getId();

            $certificateResource = new ApplePayCertificate();
            $certificateResource->setCertificate($dataBag->get(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER));
            $certificateResource->setPrivateKey($privateKeyId);
            $client->getResourceService()->createResource($certificateResource);

            $this->systemConfigService->set(sprintf('%s.%s', ConfigReader::SYSTEM_CONFIG_DOMAIN, ConfigReader::CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID), $certificateResource->getId());
        } elseif (($dataBag->has(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER) && !$dataBag->has(self::PAYMENT_PROCESSING_KEY_PARAMETER))
            || (!$dataBag->has(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER) && $dataBag->has(self::PAYMENT_PROCESSING_KEY_PARAMETER))) {
            throw new MissingCertificateFiles('Payment Processing');
        }

        if ($dataBag->has(self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER) && $dataBag->has(self::MERCHANT_IDENTIFICATION_KEY_PARAMETER)) {
            $certificate = $dataBag->get(self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER);
            $key         = $dataBag->get(self::MERCHANT_IDENTIFICATION_KEY_PARAMETER);

            // TODO: Verify certificates (if openssl is present)

            $this->filesystem->write(sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $salesChannelId, self::MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME), $certificate);
            $this->filesystem->write(sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $salesChannelId, self::MERCHANT_IDENTIFICATION_KEY_FILENAME), $key);

            $this->systemConfigService->set(sprintf('%s.%s', ConfigReader::SYSTEM_CONFIG_DOMAIN, ConfigReader::CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFICATION_CERTIFICATE_ID), $salesChannelId);
        } elseif (($dataBag->has(self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER) && !$dataBag->has(self::MERCHANT_IDENTIFICATION_KEY_PARAMETER))
            || (!$dataBag->has(self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER) && $dataBag->has(self::MERCHANT_IDENTIFICATION_KEY_PARAMETER))) {
            throw new MissingCertificateFiles('Merchant Identification');
        }

        return new JsonResponse(
            null,
            201
        );
    }

    /**
     * @Route("/api/_action/unzer-payment/apple-pay/certificates/{salesChannelId}", name="api.action.unzer.apple-pay.check-certificates", methods={"GET"}, defaults={"salesChannelId": null, "_route_scope": {"api"}})
     * @Route("/api/v{version}/_action/unzer-payment/apple-pay/certificates/{salesChannelId}", name="api.action.unzer.apple-pay.check-certificates.version", methods={"GET"}, defaults={"salesChannelId": null, "_route_scope": {"api"}})
     */
    public function checkApplePayCertificates(RequestDataBag $dataBag): JsonResponse
    {
        $salesChannelId              = $dataBag->get('salesChannelId');
        $paymentProcessingValid      = false;
        $merchantIdentificationValid = false;

        if ($this->filesystem->has(sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $salesChannelId, self::MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME)) &&
            $this->filesystem->has(sprintf('%s/%s/%s', self::APPLE_PAY_CERTIFICATE_PATH, $salesChannelId, self::MERCHANT_IDENTIFICATION_KEY_FILENAME))) {
            // TODO: Check if certificates are present and if they're not expired (if openssl is present)
            $merchantIdentificationValid = true;
        }

        $configuration = $this->configReader->read($salesChannelId, true);

        if ($configuration->get(ConfigReader::CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID)) {
            $paymentProcessingValid = true;
        }

        return new JsonResponse(
            null,
            ($paymentProcessingValid && $merchantIdentificationValid) ? Response::HTTP_OK : Response::HTTP_NOT_FOUND
        );
    }
}
