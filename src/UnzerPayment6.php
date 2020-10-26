<?php

declare(strict_types=1);

namespace UnzerPayment6;

use Doctrine\DBAL\Connection;
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
use UnzerPayment6\Components\UnzerPaymentClassLoader;
use UnzerPayment6\Installers\CustomFieldInstaller;
use UnzerPayment6\Installers\PaymentInstaller;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    (new UnzerPaymentClassLoader())->register();
}

class UnzerPayment6 extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection'));
        $loader->load('container.xml');

        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $installContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->install($installContext);
        (new CustomFieldInstaller($customFieldSetRepository))->install($installContext);
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $updateContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->update($updateContext);
        (new CustomFieldInstaller($customFieldSetRepository))->update($updateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $activateContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->activate($activateContext);
        (new CustomFieldInstaller($customFieldSetRepository))->activate($activateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->deactivate($deactivateContext);
        (new CustomFieldInstaller($customFieldSetRepository))->deactivate($deactivateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        (new PaymentInstaller($paymentRepository))->uninstall($uninstallContext);

        if (!$uninstallContext->keepUserData()) {
            (new CustomFieldInstaller($customFieldSetRepository))->uninstall($uninstallContext);
            $this->container->get(Connection::class)->exec('
                DROP TABLE IF EXISTS `unzer_payment_transfer_info`;
                DROP TABLE IF EXISTS `unzer_payment_payment_device`;
            ');
        }
    }
}
