<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1564149494AddPaymentDeviceVault extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1564149494;
    }

    public function update(Connection $connection): void
    {
        $result = $connection->fetchOne('SHOW TABLES LIKE \'unzer_payment_payment_device\';');

        if ($result) {
            return;
        }

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `heidelpay_payment_device` (
                `id` binary(16) NOT NULL,
                `customer_id` BINARY(16) NOT NULL,
                `device_type` VARCHAR(16) NOT NULL,
                `type_id` VARCHAR(32) NOT NULL,
                `data` TEXT NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,

                PRIMARY KEY (`id`),
                KEY `fk.heidelpay_payment_device.customer_id` (`customer_id`),

                CONSTRAINT `fk.heidelpay_payment_device.customer_id`
                    FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
