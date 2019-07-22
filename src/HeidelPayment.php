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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

class HeidelPayment extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection'));
        $loader->load('services.xml');

        parent::build($container);
    }

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
