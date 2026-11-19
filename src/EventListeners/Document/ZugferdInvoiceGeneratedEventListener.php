<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Document;

use Shopware\Core\Checkout\Document\Zugferd\ZugferdInvoiceGeneratedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnzerPayment6\DataAbstractionLayer\Entity\TransferInfo\UnzerPaymentTransferInfoEntity;
use UnzerPayment6\Installer\CustomFieldInstaller;
use UnzerPayment6\Installer\PaymentInstaller;

readonly class ZugferdInvoiceGeneratedEventListener implements EventSubscriberInterface
{
    private const ACCOUNT_INFO_PAYMENT_METHODS = [
        PaymentInstaller::PAYMENT_ID_PRE_PAYMENT,
        PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE,
        PaymentInstaller::PAYMENT_ID_PAYLATER_INSTALLMENT,
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            ZugferdInvoiceGeneratedEvent::class => 'onZugferdInvoiceGenerated',
        ];
    }

    public function onZugferdInvoiceGenerated(ZugferdInvoiceGeneratedEvent $event): void
    {
        $transaction = $event->order->getPrimaryOrderTransaction() ?? $event->order->getTransactions()?->last();

        if (!$transaction instanceof OrderTransactionEntity) {
            return;
        }

        $paymentMethodId = $transaction->getPaymentMethodId();

        if (!$this->isUnzerPaymentMethod($paymentMethodId)) {
            return;
        }

        $transferInfo = $this->getTransferInfo($transaction);
        $descriptor = $transferInfo['descriptor'] ?? null;
        $iban = $transferInfo['iban'] ?? null;

        if (!empty($iban) && \in_array($paymentMethodId, self::ACCOUNT_INFO_PAYMENT_METHODS, true)) {
            $event->document->getBuilder()->addDocumentPaymentMeanToCreditTransfer(
                $iban,
                $transferInfo['holder'] ?? null,
                null,
                $transferInfo['bic'] ?? null,
                !empty($descriptor) ? $descriptor : null
            );

            return;
        }
    }

    private function isUnzerPaymentMethod(string $paymentMethodId): bool
    {
        return \in_array($paymentMethodId, PaymentInstaller::PAYMENT_METHOD_IDS, true);
    }

    /**
     * @return array{iban?: string|null, bic?: string|null, holder?: string|null, descriptor?: string|null}|null
     */
    private function getTransferInfo(OrderTransactionEntity $transaction): ?array
    {
        $extension = $transaction->getExtension('unzerTransferInfo');

        if ($extension instanceof UnzerPaymentTransferInfoEntity) {
            $vars = $extension->getVars();

            return [
                'iban' => $vars['iban'] ?? null,
                'bic' => $vars['bic'] ?? null,
                'holder' => $vars['holder'] ?? null,
                'descriptor' => $vars['descriptor'] ?? null,
            ];
        }

        $transferInfo = ($transaction->getCustomFields() ?? [])[CustomFieldInstaller::UNZER_PAYMENT_TRANSFER_INFO] ?? null;

        if (!\is_array($transferInfo)) {
            return null;
        }

        return [
            'iban' => $transferInfo['iban'] ?? null,
            'bic' => $transferInfo['bic'] ?? null,
            'holder' => $transferInfo['holder'] ?? null,
            'descriptor' => $transferInfo['descriptor'] ?? null,
        ];
    }
}
