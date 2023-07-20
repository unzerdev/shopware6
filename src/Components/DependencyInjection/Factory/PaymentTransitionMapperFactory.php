<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\DependencyInjection\Factory;

use UnzerPayment6\Components\PaymentTransitionMapper\AbstractTransitionMapper;
use UnzerPayment6\Components\PaymentTransitionMapper\Exception\NoTransitionMapperFoundException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

class PaymentTransitionMapperFactory
{
    /** @var AbstractTransitionMapper[] */
    protected iterable $transitionMapperCollection = [];

    public function __construct(iterable $transitionMapperCollection)
    {
        $this->transitionMapperCollection = $transitionMapperCollection;
    }

    public function getTransitionMapper(BasePaymentType $paymentType): AbstractTransitionMapper
    {
        foreach ($this->transitionMapperCollection as $transitionMapper) {
            if ($transitionMapper->supports($paymentType)) {
                return $transitionMapper;
            }
        }

        throw new NoTransitionMapperFoundException($paymentType::getResourceName());
    }
}
