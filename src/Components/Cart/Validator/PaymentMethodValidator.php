<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Cart\Validator;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerPayment6\UnzerPayment6;

class PaymentMethodValidator implements CartValidatorInterface
{
    private EntityRepository $pluginRepository;

    public function __construct(EntityRepository $pluginRepository)
    {
        $this->pluginRepository = $pluginRepository;
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if (!in_array($context->getPaymentMethod()->getId(), PaymentInstaller::PAYMENT_METHOD_IDS)) {
            return;
        }

        if (!$this->getPluginByName(UnzerPayment6::PLUGIN_NAME, $context->getContext())->getActive()) {
            $errors->add(new PaymentMethodBlockedError((string) $context->getPaymentMethod()->getTranslation('name')));

            return;
        }

        if ($context->getPaymentMethod()->getId() !== PaymentInstaller::PAYMENT_ID_PAYLATER_INSTALLMENT) {
            return;
        }

        if (!in_array($context->getCurrency()->getIsoCode(), ['EUR', 'CHF'])) {
            $errors->add(new PaymentMethodBlockedError((string) $context->getPaymentMethod()->getTranslation('name')));

            return;
        }

        if (!$context->getCustomer() || !$context->getCustomer()->getActiveBillingAddress() || !$context->getCustomer()->getActiveBillingAddress()->getCountry()) {
            return;
        }

        if (!in_array($context->getCustomer()->getActiveBillingAddress()->getCountry()->getIso(), ['DE', 'AT', 'CH'])) {
            $errors->add(new PaymentMethodBlockedError((string) $context->getPaymentMethod()->getTranslation('name')));
        }
    }

    private function getPluginByName(string $pluginName, Context $context): PluginEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $pluginName));

        return $this->pluginRepository->search($criteria, $context)->first();
    }
}
