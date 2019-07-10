<?php

declare(strict_types=1);

namespace HeidelPayment;

use HeidelPayment\Installers\PaymentInstaller;
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
        (new PaymentInstaller($this->container->get('payment_method.repository')))->install($installContext);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $activateContext): void
    {
        (new PaymentInstaller($this->container->get('payment_method.repository')))->activate($activateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        (new PaymentInstaller($this->container->get('payment_method.repository')))->deactivate($deactivateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        (new PaymentInstaller($this->container->get('payment_method.repository')))->uninstall($uninstallContext);
    }
}
