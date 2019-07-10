<?php

declare(strict_types=1);

namespace HeidelPayment;

use HeidelPayment\Installers\PaymentInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class HeidelPayment extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $installContext): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('payment_method.repository');

        (new PaymentInstaller($repository))->install($installContext);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $activateContext): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('payment_method.repository');

        (new PaymentInstaller($repository))->activate($activateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('payment_method.repository');

        (new PaymentInstaller($repository))->deactivate($deactivateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('payment_method.repository');

        (new PaymentInstaller($repository))->uninstall($uninstallContext);
    }
}
