<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentFrame;

use UnzerPayment6\Installer\PaymentInstaller;

interface PaymentFrameFactoryInterface
{
    public const DEFAULT_FRAME_MAPPING = [
        PaymentInstaller::PAYMENT_ID_CREDIT_CARD             => '@Storefront/storefront/component/unzer/frames/credit-card.html.twig',
        PaymentInstaller::PAYMENT_ID_INVOICE                 => '@Storefront/storefront/component/unzer/frames/invoice.html.twig',
        PaymentInstaller::PAYMENT_ID_INVOICE_GUARANTEED      => '@Storefront/storefront/component/unzer/frames/invoice-guaranteed.html.twig',
        PaymentInstaller::PAYMENT_ID_INVOICE_FACTORING       => '@Storefront/storefront/component/unzer/frames/invoice-factoring.html.twig',
        PaymentInstaller::PAYMENT_ID_EPS                     => '@Storefront/storefront/component/unzer/frames/eps.html.twig',
        PaymentInstaller::PAYMENT_ID_IDEAL                   => '@Storefront/storefront/component/unzer/frames/ideal.html.twig',
        PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT            => '@Storefront/storefront/component/unzer/frames/sepa-direct-debit.html.twig',
        PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT_GUARANTEED => '@Storefront/storefront/component/unzer/frames/sepa-direct-debit-guaranteed.html.twig',
        PaymentInstaller::PAYMENT_ID_HIRE_PURCHASE           => '@Storefront/storefront/component/unzer/frames/hire-purchase.html.twig',
        PaymentInstaller::PAYMENT_ID_PAYPAL                  => '@Storefront/storefront/component/unzer/frames/paypal.html.twig',
    ];

    public function getPaymentFrame(string $paymentMethodId): ?string;
}
