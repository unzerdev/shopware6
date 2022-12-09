<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\ApplePay\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class InvalidCertificate extends ShopwareHttpException
{
    public function __construct(string $certificateType)
    {
        parent::__construct('Invalid certificate given for {{ certificateType }}', ['certificateType' => $certificateType]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'UNZER_PAYMENT__INVALID_CERTIFICATE';
    }
}
