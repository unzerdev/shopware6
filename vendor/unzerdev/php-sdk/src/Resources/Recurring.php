<?php

namespace UnzerSDK\Resources;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Traits\HasAdditionalTransactionData;
use UnzerSDK\Traits\HasCustomerMessage;
use UnzerSDK\Traits\HasDate;
use UnzerSDK\Traits\HasRecurrenceType;
use UnzerSDK\Traits\HasStates;
use UnzerSDK\Traits\HasUniqueAndShortId;

/**
 * This represents the Recurring resource.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Recurring extends AbstractUnzerResource
{
    use HasStates;
    use HasUniqueAndShortId;
    use HasCustomerMessage;
    use HasDate;
    use HasAdditionalTransactionData;
    use HasRecurrenceType;

    /** @var string $returnUrl */
    protected $returnUrl;

    /** @var string|null $redirectUrl */
    protected $redirectUrl;

    /** @var string $paymentTypeId */
    private $paymentTypeId;

    /**
     * @param string $paymentType
     * @param string $returnUrl
     */
    public function __construct(string $paymentType, string $returnUrl)
    {
        $this->returnUrl     = $returnUrl;
        $this->paymentTypeId = $paymentType;
    }

    /**
     * @return string
     */
    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     *
     * @return Recurring
     */
    public function setReturnUrl(string $returnUrl): Recurring
    {
        $this->returnUrl = $returnUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentTypeId(): string
    {
        return $this->paymentTypeId;
    }

    /**
     * @param string $paymentTypeId
     *
     * @return Recurring
     */
    public function setPaymentTypeId(string $paymentTypeId): Recurring
    {
        $this->paymentTypeId = $paymentTypeId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string|null $redirectUrl
     *
     * @return Recurring
     */
    protected function setRedirectUrl(?string $redirectUrl): Recurring
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        $parts = [
            'types',
            $this->paymentTypeId,
            parent::getResourcePath($httpMethod)
        ];

        return implode('/', $parts);
    }
}
