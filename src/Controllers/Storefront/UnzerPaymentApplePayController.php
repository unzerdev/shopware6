<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Storefront;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;

/**
 * @RouteScope(scopes={"storefront"})
 */
class UnzerPaymentApplePayController extends StorefrontController
{
    private const MERCHANT_VALIDATION_URL_PARAM = 'merchantValidationUrl';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/unzer/applePay/validateMerchant", name="unzer.apple_pay.validate_merchant", methods={"POST"}, defaults={"csrf_protected": false, "_route_scope": {"storefront"}})
     */
    public function validateMerchant(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $applePaySession = new ApplepaySession('your.merchantIdentifier', 'your.merchantName', 'your.domainName');
        $appleAdapter    = new ApplepayAdapter();

        // TODO: Fetch certificate content from private filesystem
        // TODO: Copy certificates to local path, so appleAdapter can pick them up as actual file paths.
        // TODO: Only do that, if the file is not already there
        $appleAdapter->init('/path/to/merchant_id.pem', '/path/to/rsakey.key');

        $merchantValidationUrl = urldecode($request->get(self::MERCHANT_VALIDATION_URL_PARAM));

        try {
            $validationResponse = $appleAdapter->validateApplePayMerchant(
                $merchantValidationUrl,
                $applePaySession
            );

            return new Response($validationResponse);
        } catch (\Exception $e) {
            $this->logger->error('Error in Apple Pay merchant validation', ['exception' => $e]);

            throw $e;
        }
    }
}
