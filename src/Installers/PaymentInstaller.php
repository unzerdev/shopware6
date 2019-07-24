<?php

declare(strict_types=1);

namespace HeidelPayment\Installers;

use HeidelPayment\Components\PaymentHandler\HeidelCreditCardPaymentHandler;
use HeidelPayment\Components\PaymentHandler\HeidelInvoicePaymentHandler;
use HeidelPayment\Components\PaymentHandler\HeidelSofortPaymentHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class PaymentInstaller implements InstallerInterface
{
    public const PAYMENT_ID_CREDIT_CARD = '4673044aff79424a938d42e9847693c3';
    public const PAYMENT_ID_SOFORT      = '95aa098aac8f11e9a2a32a2ae2dbcce4';
    public const PAYMENT_ID_INVOICE     = '08fb8d9a72ab4ca62b811e74f2eca79f';

    public const PAYMENT_METHODS = [
        [
            'id'                => self::PAYMENT_ID_CREDIT_CARD,
            'handlerIdentifier' => HeidelCreditCardPaymentHandler::class,
            'name'              => 'Credit card (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Kreditkarte (heidelpay)',
                    'description' => 'Kreditkartenzahlungen mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Credit card (heidelpay)',
                    'description' => 'Credit card payments with heidelpay',
                ],
            ],
            'customFields' => [
                'heidelpay_frame' => '@Storefront/component/heidelpay/frames/credit-card.html.twig',
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_INVOICE,
            'handlerIdentifier' => HeidelInvoicePaymentHandler::class,
            'name'              => 'Invoice (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Rechnung (heidelpay)',
                    'description' => 'Rechnungskauf mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Invoice (heidelpay)',
                    'description' => 'Invoice payments with heidelpay',
                ],
            ],
            'customFields' => [
                'heidelpay_frame' => '@Storefront/component/heidelpay/frames/invoice.html.twig',
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_SOFORT,
            'handlerIdentifier' => HeidelSofortPaymentHandler::class,
            'name'              => 'Sofort (heidelpay)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Sofort (heidelpay)',
                    'description' => 'Sofort mit Heidelpay',
                ],
                'en-GB' => [
                    'name'        => 'Sofort (heidelpay)',
                    'description' => 'Sofort with heidelpay',
                ],
            ],
        ],
    ];

    /** @var EntityRepositoryInterface */
    private $paymentMethodRepository;

    public function __construct(EntityRepositoryInterface $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function install(InstallContext $context): void
    {
        $this->paymentMethodRepository->upsert(self::PAYMENT_METHODS, $context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        $this->paymentMethodRepository->upsert(self::PAYMENT_METHODS, $context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->setAllPaymentMethodsActive(false, $context);
    }

    public function activate(ActivateContext $context): void
    {
        $this->setAllPaymentMethodsActive(true, $context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->setAllPaymentMethodsActive(false, $context);
    }

    private function setAllPaymentMethodsActive(bool $active, InstallContext $context): void
    {
        $upsertPayload = [];
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $upsertPayload[] = [
                'id'     => $paymentMethod['id'],
                'active' => $active,
            ];
        }

        $this->paymentMethodRepository->upsert($upsertPayload, $context->getContext());
    }
}
