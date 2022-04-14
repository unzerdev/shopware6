<?php

declare(strict_types=1);

namespace UnzerPayment6\Installer;

use Cassandra\Custom;
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
    public const UNZER_PAYMENT_PAYMENT_ID_KEY = 'unzer_pay_payment_id';
    public const UNZER_PAYMENT_TRANSFER_INFO  = 'unzer_payment_transfer_info';

    public const CUSTOM_FIELDS = [
        [
            'id'           => '051351c0e4e64229a9a29b9893344d23',
            'name'         => 'custom_unzer_payment',
            'global'       => true,
            'customFields' => [
                [
                    'id'   => '6bb838751d65478992a5c0a1e80cb5fd',
                    'name' => self::UNZER_PAYMENT_IS_TRANSACTION,
                    'type' => CustomFieldTypes::BOOL,
                ],
                [
                    'id'   => '4962176184c25acbd46f60a15c24b334',
                    'name' => self::UNZER_PAYMENT_IS_SHIPPED,
                    'type' => CustomFieldTypes::BOOL,
                ],
                [
                    'id'   => 'ce3728a208204885a74552548147e985',
                    'name' => self::UNZER_PAYMENT_PAYMENT_ID_KEY,
                    'type' => CustomFieldTypes::TEXT,
                ],
                [
                    'id'   => 'aae342fddd464116839222049bf26fd8',
                    'name' => self::UNZER_PAYMENT_TRANSFER_INFO,
                    'type' => CustomFieldTypes::JSON,
                ],
            ],
        ],
    ];

    /** @var EntityRepositoryInterface */
    private $customFieldSetRepository;

    public function __construct(EntityRepositoryInterface $customFieldSetRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        $this->customFieldSetRepository->upsert(self::CUSTOM_FIELDS, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        $this->customFieldSetRepository->upsert(self::CUSTOM_FIELDS, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        $this->customFieldSetRepository->delete(self::CUSTOM_FIELDS, $context->getContext());
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
