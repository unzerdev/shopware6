<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1566288547AddAddressHash extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1566288547;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            ALTER TABLE `heidelpay_payment_device`
            ADD COLUMN `address_hash` VARCHAR(32) NOT NULL AFTER `data`;
SQL;

        try {
            $connection->exec($sql);
        } catch (DBALException $ex) {
            //The column may exist already
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
