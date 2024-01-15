<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentFrame;

use UnzerPayment6\Installer\PaymentInstaller;

interface PaymentFrameFactoryInterface
{
    public const DEFAULT_FRAME_MAPPING = [
        PaymentInstaller::PAYMENT_ID_CREDIT_CARD          => '@Storefront/storefront/component/unzer/frames/credit-card.html.twig',
        PaymentInstaller::PAYMENT_ID_INVOICE              => '@Storefront/storefront/component/unzer/frames/invoice.html.twig',
        PaymentInstaller::PAYMENT_ID_INVOICE_SECURED      => '@Storefront/storefront/component/unzer/frames/invoice-secured.html.twig',
        PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE     => '@Storefront/storefront/component/unzer/frames/paylater-invoice.html.twig',
        PaymentInstaller::PAYMENT_ID_EPS                  => '@Storefront/storefront/component/unzer/frames/eps.html.twig',
        PaymentInstaller::PAYMENT_ID_IDEAL                => '@Storefront/storefront/component/unzer/frames/ideal.html.twig',
        PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT         => '@Storefront/storefront/component/unzer/frames/sepa-direct-debit.html.twig',
        PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT_SECURED => '@Storefront/storefront/component/unzer/frames/sepa-direct-debit-secured.html.twig',
        PaymentInstaller::PAYMENT_ID_INSTALLMENT_SECURED  => '@Storefront/storefront/component/unzer/frames/installment-secured.html.twig',
        PaymentInstaller::PAYMENT_ID_PAYPAL               => '@Storefront/storefront/component/unzer/frames/paypal.html.twig',
        PaymentInstaller::PAYMENT_ID_APPLE_PAY            => '@Storefront/storefront/component/unzer/frames/apple-pay.html.twig',
        PaymentInstaller::PAYMENT_ID_PAYLATER_INSTALLMENT => '@Storefront/storefront/component/unzer/frames/paylater-installment.html.twig',
    ];

    public function getPaymentFrame(string $paymentMethodId): ?string;
}
