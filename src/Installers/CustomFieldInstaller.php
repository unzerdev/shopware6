<?php

declare(strict_types=1);

namespace HeidelPayment\Installers;

use Shopware\Core\Framework\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class CustomFieldInstaller implements InstallerInterface
{
    public const CUSTOM_FIELD_FRAME = 'heidelpay_frame';

    public const CUSTOM_FIELDS = [
        [
            'id'     => '051351c0e4e64229a9a29b9893344d23',
            'name'   => 'custom_Heidelpay',
            'config' => [
                'label' => [
                    'en-GB' => 'Heidelpay',
                    'de-DE' => 'Heidelpay',
                ],
            ],
            'customFields' => [
                [
                    'name'   => 'heidelpay_frame',
                    'type'   => CustomFieldTypes::TEXT,
                    'id'     => 'ef604f17f5be45ccbe3fe9315aac8a84',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Checkout template',
                            'de-DE' => 'Kassen-Template',
                        ],
                    ],
                ],
                [
                    'name'   => 'heidelpay_transaction',
                    'type'   => CustomFieldTypes::BOOL,
                    'id'     => '6bb838751d65478992a5c0a1e80cb5fd',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Heidelpay transaction',
                            'de-DE' => 'Heidelpay Transaktion',
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
