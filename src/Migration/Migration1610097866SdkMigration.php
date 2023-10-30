<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1610097866SdkMigration extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610097866;
    }

    public function update(Connection $connection): void
    {
        // update config key
        $connection->executeStatement(<<<SQL
            UPDATE `system_config`
            SET `configuration_key` = REPLACE(`configuration_key`, 'hirePurchase', 'installmentSecured')
            WHERE `configuration_key` LIKE "UnzerPayment6.settings.hirePurchase%"
SQL
        );

        // disable invoiceGuaranteed
        $connection->executeStatement(<<<SQL
            UPDATE `payment_method`
            SET `active` = 0, `after_order_enabled` = 0
            WHERE `id` = UNHEX('78F3CFA6AB2D9168759724E7CDE1EAB2')
SQL
        );

        // update paymentDevice types
        $connection->executeStatement(<<<SQL
            UPDATE `unzer_payment_payment_device`
            SET `device_type` = 'direct_debit_secured'
            WHERE `device_type` = 'direct_debit_guaranteed'
SQL
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        //Nothing to do
    }
}
