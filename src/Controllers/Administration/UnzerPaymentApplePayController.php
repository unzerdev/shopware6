<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Administration;

use DateTimeImmutable;
use Exception;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UnzerPayment6\Components\ApplePay\CertificateManager;
use UnzerPayment6\Components\ApplePay\Exception\InvalidCertificate;
use UnzerPayment6\Components\ApplePay\Exception\MissingCertificateFiles;
use UnzerPayment6\Components\ApplePay\Struct\CertificateInformation;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\Resource\ApplePayCertificate;
use UnzerPayment6\Components\Resource\ApplePayPrivateKey;
use UnzerPayment6\Components\Struct\KeyPairContext;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;

#[Route(defaults: ['_routeScope' => ['api']])]
class UnzerPaymentApplePayController extends AbstractController
{
    private const INHERIT_PAYMENT_PROCESSING_PARAMETER          = 'inheritPaymentProcessing';
    private const PAYMENT_PROCESSING_CERTIFICATE_PARAMETER      = 'paymentProcessingCertificate';
    private const PAYMENT_PROCESSING_KEY_PARAMETER              = 'paymentProcessingKey';
    private const INHERIT_MERCHANT_IDENTIFICATION_PARAMETER     = 'inheritMerchantIdentification';
    private const MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER = 'merchantIdentificationCertificate';
    private const MERCHANT_IDENTIFICATION_KEY_PARAMETER         = 'merchantIdentificationKey';



    public function __construct(
        private readonly ClientFactoryInterface $clientFactory,
        private readonly LoggerInterface $logger,
        private readonly SystemConfigService $systemConfigService,
        private readonly Filesystem $filesystem,
        private readonly ConfigReaderInterface $configReader,
        private readonly CertificateManager $certificateManager,
        private readonly EntityRepository $salesChannelRepository
    ) {
    }
    #[Route(path: '/api/_action/unzer-payment/apple-pay/certificates/{salesChannelId}', name: 'api.action.unzer.apple-pay.update-certificates', defaults: ['salesChannelId' => null], methods: ['POST'])]
    public function updateApplePayCertificates(?string $salesChannelId, RequestDataBag $dataBag): JsonResponse
    {


        if ($dataBag->has(self::INHERIT_PAYMENT_PROCESSING_PARAMETER)) {
            $this->logger->debug(sprintf('Payment Processing reference for sales channel %s cleared', $salesChannelId));
            $this->systemConfigService->delete(sprintf('%s%s', ConfigReader::SYSTEM_CONFIG_DOMAIN, ConfigReader::CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID), $salesChannelId);
        }

        try {
            $this->updatePaymentProcessingCertificate($dataBag, $salesChannelId);

            if ($dataBag->has(self::INHERIT_MERCHANT_IDENTIFICATION_PARAMETER)) {
                if ($this->filesystem->has($this->certificateManager->getMerchantIdentificationCertificatePathForUpdate($salesChannelId))) {
                    $this->logger->debug(sprintf('Merchant Identification certificate for sales channel %s deleted', $salesChannelId));
                    $this->filesystem->delete($this->certificateManager->getMerchantIdentificationCertificatePathForUpdate($salesChannelId));
                }

                if ($this->filesystem->has($this->certificateManager->getMerchantIdentificationKeyPathForUpdate($salesChannelId))) {
                    $this->logger->debug(sprintf('Merchant Identification key for sales channel %s deleted', $salesChannelId));
                    $this->filesystem->delete($this->certificateManager->getMerchantIdentificationKeyPathForUpdate($salesChannelId));
                }

                $this->systemConfigService->delete(sprintf('%s%s', ConfigReader::SYSTEM_CONFIG_DOMAIN, ConfigReader::CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFICATION_CERTIFICATE_ID), $salesChannelId);
            }

            if ($dataBag->get(self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER) && $dataBag->get(self::MERCHANT_IDENTIFICATION_KEY_PARAMETER)) {
                $certificate = $dataBag->get(self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER);
                $key         = $dataBag->get(self::MERCHANT_IDENTIFICATION_KEY_PARAMETER);

                if (extension_loaded('openssl') && !openssl_x509_parse($certificate)) {
                    $this->logger->error('Invalid Merchant Identification certificate given');
                    throw new InvalidCertificate('Merchant Identification');
                }

                $this->filesystem->write($this->certificateManager->getMerchantIdentificationCertificatePathForUpdate($salesChannelId), $certificate);
                $this->filesystem->write($this->certificateManager->getMerchantIdentificationKeyPathForUpdate($salesChannelId), $key);

                $this->systemConfigService->set(sprintf('%s%s', ConfigReader::SYSTEM_CONFIG_DOMAIN, ConfigReader::CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFICATION_CERTIFICATE_ID), (string) $salesChannelId, $salesChannelId);
                $this->logger->debug(sprintf('Merchant Identification certificate for sales channel %s updated', $salesChannelId));
            } elseif (($dataBag->get(self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER) && !$dataBag->get(self::MERCHANT_IDENTIFICATION_KEY_PARAMETER))
                || (!$dataBag->get(self::MERCHANT_IDENTIFICATION_CERTIFICATE_PARAMETER) && $dataBag->get(self::MERCHANT_IDENTIFICATION_KEY_PARAMETER))) {
                $this->logger->error('Merchant Identification certificate or key missing');
                throw new MissingCertificateFiles('Merchant Identification');
            }
        } catch (UnzerApiException $e) {
            return new JsonResponse(
                [
                    'message'         => $e->getMerchantMessage(),
                    'translationData' => [],
                ],
                Response::HTTP_BAD_REQUEST
            );
        } catch (Exception $e) {
            if (method_exists($e, 'getTranslationKey')) {
                $message = $e->getTranslationKey();
            } else {
                $message = $e->getMessage();
            }

            if (method_exists($e, 'getTranslationData')) {
                $translationData = $e->getTranslationData();
            } else {
                $translationData = [];
            }

            return new JsonResponse(
                [
                    'message'         => $message,
                    'translationData' => $translationData,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(
            null,
            201
        );
    }

    protected function updatePaymentProcessingCertificate(RequestDataBag $dataBag, ?string $salesChannelId):void{
        if ($dataBag->get(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER) && $dataBag->get(self::PAYMENT_PROCESSING_KEY_PARAMETER)) {
            $client = $this->clientFactory->createClient(KeyPairContext::createFromSalesChannel($this->getSalesChannel($salesChannelId)));
            $certificate = $dataBag->get(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER);

            if (extension_loaded('openssl') && !openssl_x509_parse($certificate)) {
                $this->logger->error('Invalid Payment Processing certificate given');
                throw new InvalidCertificate('Payment Processing');
            }

            $privateKeyResource = new ApplePayPrivateKey();
            $privateKeyResource->setCertificate($dataBag->get(self::PAYMENT_PROCESSING_KEY_PARAMETER));

            $client->getResourceService()->createResource($privateKeyResource->setParentResource($client));
            /** @var string $privateKeyId */
            $privateKeyId = $privateKeyResource->getId();

            $certificateResource = new ApplePayCertificate();
            $certificateResource->setCertificate($certificate);
            $certificateResource->setPrivateKey($privateKeyId);
            $client->getResourceService()->createResource($certificateResource->setParentResource($client));

            if($this->activateCertificate($certificateResource->getId(), $client)){
                $this->systemConfigService->set(sprintf('%s%s', ConfigReader::SYSTEM_CONFIG_DOMAIN, ConfigReader::CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID), $certificateResource->getId(), $salesChannelId);
            }else{
                $this->logger->error('Failed to activate Payment Processing certificate '.$certificateResource->getId());
                throw new MissingCertificateFiles('Payment Processing');
            }
            $this->logger->debug(sprintf('Payment Processing certificate for sales channel %s updated and activated', $salesChannelId));
        } elseif (($dataBag->get(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER) && !$dataBag->get(self::PAYMENT_PROCESSING_KEY_PARAMETER))
            || (!$dataBag->get(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER) && $dataBag->get(self::PAYMENT_PROCESSING_KEY_PARAMETER))) {
            $this->logger->error('Payment Processing certificate or key missing');
            throw new MissingCertificateFiles('Payment Processing');
        }
    }

    public function activateCertificate( string $certificateId, Unzer $unzerClient ): bool {
        $certificate  = ( new ApplePayCertificate() )
            ->setId( $certificateId )
            ->setParentResource( $unzerClient );
        $responseJson = $unzerClient->getHttpService()->send(
            '/keypair/applepay/certificates/' . $certificateId . '/activate',
            $certificate,
            HttpAdapterInterface::REQUEST_POST
        );
        $response     = json_decode( $responseJson, true );
        return $response['active'] ?? false;
    }

    #[Route(path: '/api/_action/unzer-payment/apple-pay/certificates/{salesChannelId}', name: 'api.action.unzer.apple-pay.check-certificates', defaults: ['salesChannelId' => null], methods: ['GET'])]
    public function checkApplePayCertificates(RequestDataBag $dataBag): JsonResponse
    {
        $salesChannelId                   = $dataBag->get('salesChannelId', '');
        $paymentProcessingValid           = false;
        $paymentProcessingActive          = false;
        $paymentProcessingInherited       = false;
        $merchantIdentificationValid      = false;
        $merchantIdentificationInherited  = false;
        $merchantIdentificationValidUntil = null;

        if (!empty($salesChannelId) && $this->filesystem->has($this->certificateManager->getMerchantIdentificationCertificatePath($salesChannelId)) &&
            $this->filesystem->has($this->certificateManager->getMerchantIdentificationKeyPath($salesChannelId))) {
            $merchantIdentificationValid = true;

            if (extension_loaded('openssl')) {
                $certificateData = openssl_x509_parse($this->filesystem->read($this->certificateManager->getMerchantIdentificationCertificatePath($salesChannelId)));

                if (is_array($certificateData) && array_key_exists('validTo_time_t', $certificateData)) {
                    $merchantIdentificationValidUntil = DateTimeImmutable::createFromFormat('U', (string) $certificateData['validTo_time_t']) ?: null;
                }
            }
        } elseif ($this->filesystem->has($this->certificateManager->getMerchantIdentificationCertificatePath('')) &&
            $this->filesystem->has($this->certificateManager->getMerchantIdentificationKeyPath(''))) {
            $merchantIdentificationValid     = true;
            $merchantIdentificationInherited = true;

            if (extension_loaded('openssl')) {
                $certificateData = openssl_x509_parse($this->filesystem->read($this->certificateManager->getMerchantIdentificationCertificatePath('')));

                if (is_array($certificateData) && array_key_exists('validTo_time_t', $certificateData)) {
                    $merchantIdentificationValidUntil = DateTimeImmutable::createFromFormat('U', (string) $certificateData['validTo_time_t']) ?: null;
                }
            }
        }

        $configuration     = $this->configReader->read($salesChannelId, false);
        $baseConfiguration = $this->configReader->read('');

        if ($configuration->get(ConfigReader::CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID)) {
            $paymentProcessingValid = true;
            $certificateId = $configuration->get(ConfigReader::CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID);
            $unzerClient = $this->clientFactory->createClient(KeyPairContext::createFromSalesChannel($this->getSalesChannel($salesChannelId)));

        } elseif ($baseConfiguration->get(ConfigReader::CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID)) {
            $paymentProcessingValid     = true;
            $paymentProcessingInherited = true;
            $certificateId = $baseConfiguration->get(ConfigReader::CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID);
            $unzerClient = $this->clientFactory->createClient(KeyPairContext::createFromSalesChannel($this->getSalesChannel('')));
        }

        if(!empty($certificateId) && !empty($unzerClient) && $unzerClient instanceof Unzer) {
            $certificateResource = new ApplePayCertificate();
            $certificateResource->setId($certificateId);
            $certificateResource->setParentResource($unzerClient);
            $submittedCertificate = $unzerClient->getResourceService()->fetchResource($certificateResource);
            $paymentProcessingActive = $submittedCertificate->getActive();
        }

        return new JsonResponse(
            new CertificateInformation(
                $paymentProcessingValid,
                $paymentProcessingActive,
                $paymentProcessingInherited,
                $merchantIdentificationValid,
                $merchantIdentificationInherited,
                $merchantIdentificationValidUntil
            ),
            Response::HTTP_OK
        );
    }

    protected function getSalesChannel(?string $salesChannelId): ?SalesChannelEntity
    {
        $criteria = new Criteria();

        if ($salesChannelId) {
            $criteria->setIds([$salesChannelId]);
        }

        $criteria->addAssociation('currency');
        $criteria->addAssociation('paymentMethod');

        return $this->salesChannelRepository->search($criteria, Context::createDefaultContext())->first();
    }
}
