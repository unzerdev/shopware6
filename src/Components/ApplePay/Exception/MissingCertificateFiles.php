<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ApplePay\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class MissingCertificateFiles extends ShopwareHttpException
{
    private string $certificateType;

    public function __construct(string $certificateType)
    {
        parent::__construct('You must upload certificate and private key together for {{ certificateType }}', ['certificateType' => $certificateType]);
        $this->certificateType = $certificateType;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'UNZER_PAYMENT__MISSING_CERTIFICATE_FILES';
    }

    public function getTranslationKey(): string
    {
        return 'unzer-payment-settings.apple-pay.certificates.update.error.messageMissingCertificateFiles';
    }

    public function getTranslationData(): array
    {
        return [
            'type' => $this->certificateType,
        ];
    }
}
