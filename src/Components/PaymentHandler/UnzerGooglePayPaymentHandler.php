<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentHandler;

use Exception;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnzerPayment6\Components\BookingMode;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment6\Components\PaymentHandler\Traits\CanCharge;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Unzer;

class UnzerGooglePayPaymentHandler extends AbstractUnzerPaymentHandler
{
    use CanAuthorize;
    use CanCharge;

    protected BasePaymentType $paymentType;

    /**
     * {@inheritdoc}
     */
    public function pay(
        Request                  $request,
        PaymentTransactionStruct $transaction,
        Context                  $context,
        ?Struct                  $validateStruct
    ): RedirectResponse
    {
        parent::pay($request, $transaction, $context, $validateStruct);
        $bookingMode = $this->pluginConfig->get(ConfigReader::CONFIG_KEY_GOOGLE_PAY_BOOKING_MODE, BookingMode::CHARGE);

        return $this->payWithBookingMode(
            $request,
            $transaction,
            $context,
            $validateStruct,
            $bookingMode
        );
    }

    public static function fetchChannelId(Unzer $client): string
    {
        try {
            $keyPair = $client->fetchKeyPair(true);
            foreach ($keyPair->getPaymentTypes() as $paymentType) {
                if ($paymentType->type === 'googlepay') {
                    $channelId = $paymentType->supports[0]->channel ?? null;
                    if ($channelId) {
                        return $channelId;
                    }
                }
            }
        } catch (Exception) {
            // silent to return '' at the end
        }

        // will only be reached, if no channel id was found
        return '';
    }
}
