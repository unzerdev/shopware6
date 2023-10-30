<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Throwable;

class Migration1566288547AddAddressHash extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1566288547;
    }

    public function update(Connection $connection): void
    {
        $result = $connection->fetchOne('SHOW TABLES LIKE \'unzer_payment_payment_device\';');

        if ($result) {
            return;
        }

        $sql = <<<SQL
            ALTER TABLE `heidelpay_payment_device`
            ADD COLUMN `address_hash` VARCHAR(32) NOT NULL AFTER `data`;
SQL;

        try {
            $connection->executeStatement($sql);
        } catch (Throwable $ex) {
            //The column may exist already
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
