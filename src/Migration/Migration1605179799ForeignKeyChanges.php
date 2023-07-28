<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1605179799ForeignKeyChanges extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1605179799;
    }

    public function update(Connection $connection): void
    {
        $transferInfoSql = $connection->fetchOne('SHOW KEYS FROM `unzer_payment_transfer_info` WHERE Key_name = "fk.heidelpay_transfer_info.transaction_id";');

        if (!$transferInfoSql) {
            $connection->executeStatement(<<<SQL
            ALTER TABLE unzer_payment_transfer_info
                DROP FOREIGN KEY `fk.heidelpay_transfer_info.transaction_id`,
                ADD CONSTRAINT `fk.unzer_payment_transfer_info.transaction_id`
                    FOREIGN KEY (`transaction_id`)
                    REFERENCES `order_transaction` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
SQL
            );
        }

        $paymentDeviceResult = $connection->fetchOne('SHOW KEYS FROM `unzer_payment_payment_device` WHERE Key_name = "fk.heidelpay_payment_device.customer_id";');

        if (!$paymentDeviceResult) {
            $connection->executeStatement(<<<SQL
            ALTER TABLE unzer_payment_payment_device
                DROP FOREIGN KEY `fk.heidelpay_payment_device.customer_id`,
                ADD CONSTRAINT `fk.unzer_payment_payment_device.customer_id`
                    FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
SQL
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        //Nothing to do
    }
}
