<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ApplePay\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class InvalidCertificate extends ShopwareHttpException
{
    private string $certificateType;

    public function __construct(string $certificateType)
    {
        parent::__construct('Invalid certificate given for {{ certificateType }}', ['certificateType' => $certificateType]);
        $this->certificateType = $certificateType;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'UNZER_PAYMENT__INVALID_CERTIFICATE';
    }

    public function getTranslationKey(): string
    {
        return 'unzer-payment-settings.apple-pay.certificates.update.error.messageInvalidCertificate';
    }

    public function getTranslationData(): array
    {
        return [
            'type' => $this->certificateType,
        ];
    }
}
