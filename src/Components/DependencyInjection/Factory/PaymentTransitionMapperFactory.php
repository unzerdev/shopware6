<?php

declare(strict_types=1);

namespace HeidelPayment6\Components\DependencyInjection\Factory;

use HeidelPayment6\Components\PaymentTransitionMapper\AbstractTransitionMapper;
use HeidelPayment6\Components\PaymentTransitionMapper\Exception\NoTransitionMapperFoundException;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

class PaymentTransitionMapperFactory
{
    /** @var AbstractTransitionMapper[]|iterable */
    protected $transitionMapperCollection = [];

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
