<?php

namespace HeidelPayment\Installers;

use Shopware\Core\Framework\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\InstallContext;

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
    public function update(InstallContext $context): void
    {
        $this->customFieldRepository->upsert(self::CUSTOM_FIELDS, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(InstallContext $context): void
    {
        $this->customFieldRepository->delete(self::CUSTOM_FIELDS, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function activate(InstallContext $context): void
    {
        //Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(InstallContext $context): void
    {
        //Nothing to do here
    }
}
