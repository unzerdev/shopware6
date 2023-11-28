<?php

namespace UnzerPayment6\Installer;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class RuleInstaller
{
    public const RULE_ID_PAYLATER_INSTALLMENT_PRECONDITION = '89055949469d4a318101ad203818f982';

    private EntityRepository $ruleRepository;
    private Connection $connection;

    public function __construct(EntityRepository $ruleRepository, Connection $connection)
    {
        $this->ruleRepository = $ruleRepository;
        $this->connection     = $connection;
    }

    public function install(InstallContext $context): void
    {
        $this->ruleRepository->upsert($this->getRules(), $context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        $this->ruleRepository->upsert($this->getRules(), $context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
    }

    public function activate(ActivateContext $context): void
    {
    }

    public function deactivate(DeactivateContext $context): void
    {
    }

    private function getRules(): array
    {
        $countryIds  = $this->getCountryIdsByIsoCode('DE', 'AT', 'CH');
        $currencyIds = $this->getCurrencyIdsByIsoCode('EUR', 'CHF');

        return [
            [
                'id'              => self::RULE_ID_PAYLATER_INSTALLMENT_PRECONDITION,
                'name'            => 'DACH-Region mit EUR oder CHF',
                'moduleTypes'     => [
                    'types' => ['payment'],
                ],
                'priority'        => 10,
                'translations'    => [
                    'de-DE' => [
                        'name' => 'DACH-Region mit EUR oder CHF',
                        'description' => 'Unzer Ratenzahlung (Paylater) steht nur in den angegebenen Regionen und Währungen zur Verfügung.',
                    ],
                    'en-GB' => [
                        'name' => 'DACH region and EUR or CHF',
                        'description' => 'Unzer Installment (Paylater) is only available in the named regions and currencies.',
                    ],
                ],
                'conditions'      => [
                    [
                        'id'       => '0ddc611908e14c42a62b618f28a3c3ad',
                        'type'     => 'andContainer',
                        'children' => [
                            [
                                'id'       => '7bdc284bda36475aa4b971451db78fb5',
                                'type'     => 'andContainer',
                                'children' => [
                                    [
                                        'id'    => 'a36824f177794a018983efc93a12929d',
                                        'type'  => 'customerBillingCountry',
                                        'value' => [
                                            'operator'   => '=',
                                            'countryIds' => $countryIds,
                                        ],
                                    ],
                                    [
                                        'id'    => 'c78c104da24e4325867aea719a6985a8',
                                        'type'  => 'currency',
                                        'value' => [
                                            'operator'    => '=',
                                            'currencyIds' => $currencyIds,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'payment_methods' => [
                    ['id' => PaymentInstaller::PAYMENT_ID_PAYLATER_INSTALLMENT],
                ],
            ],
        ];
    }

    private function getCountryIdsByIsoCode(string ...$iso): array
    {
        $result = $this->connection->fetchAllAssociativeIndexed('
            SELECT LOWER(HEX(`id`))
            FROM `country`
            WHERE `iso` IN (:iso);
        ', ['iso' => $iso], ['iso' => Connection::PARAM_STR_ARRAY]);

        return array_keys($result);
    }

    private function getCurrencyIdsByIsoCode(string ...$iso): array
    {
        $result = $this->connection->fetchAllAssociativeIndexed('
            SELECT LOWER(HEX(`id`))
            FROM `currency`
            WHERE `iso_code` IN (:iso);
        ', ['iso' => $iso], ['iso' => Connection::PARAM_STR_ARRAY]);

        return array_keys($result);
    }
}
