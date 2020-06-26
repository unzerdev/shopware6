<?php

declare(strict_types=1);

namespace HeidelPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1593181803AdjustPaymentDeviceVault extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1593181803;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            ALTER TABLE `heidelpay_payment_device`
            CHANGE `device_type` `device_type` varchar(32) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `customer_id`;
SQL;

        $connection->exec($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
