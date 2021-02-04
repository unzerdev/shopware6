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
        $this->migrateTransferInfo($connection);

        $this->migratePaymentDevices($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        //Nothing to do
    }

    private function migrateTransferInfo(Connection $connection): void
    {
        $transferInfoSql = $connection->fetchAssoc('SHOW KEYS FROM `unzer_payment_transfer_info` WHERE Key_name = "fk.heidelpay_transfer_info.transaction_id";');

        if (!empty($transferInfoSql)) {
            try {
                $connection->exec(<<<SQL
            ALTER TABLE unzer_payment_transfer_info
                DROP FOREIGN KEY `fk.heidelpay_transfer_info.transaction_id`
SQL
                );
            } catch (\Throwable $t) {
//                silentfail - already deleted
            }

            try {
                $connection->exec(<<<SQL
            ALTER TABLE unzer_payment_transfer_info
                DROP INDEX `fk.heidelpay_transfer_info.transaction_id`
SQL
                );
            } catch (\Throwable $t) {
//                silentfail - already deleted
            }

            try {
                $connection->exec(<<<SQL
                ALTER TABLE unzer_payment_transfer_info
                ADD CONSTRAINT `fk.unzer_payment_transfer_info.transaction_id`
                    FOREIGN KEY (`transaction_id`)
                    REFERENCES `order_transaction` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
SQL
                );
            } catch (\Throwable $t) {
//                silentfail - already created
            }
        }
    }

    private function migratePaymentDevices(Connection $connection): void
    {
        $paymentDeviceResult = $connection->fetchAssoc(
            'SHOW KEYS FROM `unzer_payment_payment_device` WHERE Key_name = "fk.heidelpay_payment_device.customer_id";'
        );

        if (!empty($paymentDeviceResult)) {
            try {
                $connection->exec(<<<SQL
            ALTER TABLE unzer_payment_payment_device
                DROP FOREIGN KEY `fk.heidelpay_payment_device.customer_id`
SQL
                );
            } catch (\Throwable $t) {
//                silentfail - already deleted
            }

            try {
                $connection->exec(<<<SQL
            ALTER TABLE unzer_payment_payment_device
                DROP INDEX `fk.heidelpay_payment_device.customer_id`
SQL
                );
            } catch (\Throwable $t) {
//                silentfail - already deleted
            }

            try {
                $connection->exec(<<<SQL
                ALTER TABLE unzer_payment_payment_device
                ADD CONSTRAINT `fk.unzer_payment_payment_device.customer_id`
                    FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
SQL
                );
            } catch (\Throwable $t) {
//                silentfail - already created
            }
        }
    }
}
