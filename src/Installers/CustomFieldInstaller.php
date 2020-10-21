<?php

declare(strict_types=1);

namespace UnzerPayment6\Installers;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldInstaller implements InstallerInterface
{
    public const UNZER_PAYMENT_IS_TRANSACTION = 'unzer_payment_is_transaction';
    public const UNZER_PAYMENT_IS_SHIPPED     = 'unzer_payment_is_shipped';

    public const CUSTOM_FIELDS = [
        [
            'id'     => '051351c0e4e64229a9a29b9893344d23',
            'name'   => 'custom_unzer_payment',
            'config' => [
                'label' => [
                    'en-GB' => 'Unzer Payment',
                    'de-DE' => 'Unzer Payment',
                ],
            ],
            'customFields' => [
                [
                    'name'   => self::UNZER_PAYMENT_IS_TRANSACTION,
                    'type'   => CustomFieldTypes::BOOL,
                    'id'     => '6bb838751d65478992a5c0a1e80cb5fd',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Unzer Payment transaction',
                            'de-DE' => 'Unzer Payment Transaktion',
                        ],
                    ],
                ],
                [
                    'name'   => self::UNZER_PAYMENT_IS_SHIPPED,
                    'type'   => CustomFieldTypes::BOOL,
                    'id'     => '4962176184c25acbd46f60a15c24b334',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Shipping notification executed',
                            'de-DE' => 'Versand-Benachrichtigung erfolgt',
                        ],
                    ],
                ],
            ],
        ],
    ];

    /** @var EntityRepositoryInterface */
    private $customFieldRepository;

    public function __construct(EntityRepositoryInterface $customFieldRepository)
    {
        $this->customFieldRepository = $customFieldRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        $this->customFieldRepository->upsert(self::CUSTOM_FIELDS, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        $this->customFieldRepository->upsert(self::CUSTOM_FIELDS, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        $this->customFieldRepository->delete(self::CUSTOM_FIELDS, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        // Nothing to do here
    }
}
