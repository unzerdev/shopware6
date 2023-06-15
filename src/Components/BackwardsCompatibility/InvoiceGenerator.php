<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\BackwardsCompatibility;

use Shopware\Core\Checkout\Document\Renderer\InvoiceRenderer;

class InvoiceGenerator
{
    public static function getInvoiceTechnicalName(): string
    {
        // TODO: Remove me if compatibility is at least 6.5.0.0
        if (class_exists('\Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator')) {
            /** @phpstan-ignore-next-line */
            return \Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator::INVOICE;
        }

        /** @phpstan-ignore-next-line */
        return InvoiceRenderer::TYPE;
    }
}
