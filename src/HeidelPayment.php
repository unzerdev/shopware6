<?php

declare(strict_types=1);

namespace HeidelPayment;

use HeidelPayment\Installers\CustomFieldInstaller;
use HeidelPayment\Installers\PaymentInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
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
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->install($installContext);
        (new CustomFieldInstaller($customFieldRepository))->install($installContext);
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $updateContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->update($updateContext);
        (new CustomFieldInstaller($customFieldRepository))->update($updateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $activateContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->activate($activateContext);
        (new CustomFieldInstaller($customFieldRepository))->activate($activateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->deactivate($deactivateContext);
        (new CustomFieldInstaller($customFieldRepository))->deactivate($deactivateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->uninstall($uninstallContext);
        (new CustomFieldInstaller($customFieldRepository))->uninstall($uninstallContext);
    }
}
