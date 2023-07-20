<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1579964350AddTransferInfoTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1579964350;
    }

    public function update(Connection $connection): void
    {
        $result = $connection->fetchOne('SHOW TABLES LIKE \'unzer_payment_transfer_info\';');

        if ($result) {
            return;
        }

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `heidelpay_transfer_info` (
                `id` binary(16) NOT NULL,
                `transaction_id` BINARY(16) NOT NULL,
                `iban` VARCHAR(34),
                `bic` VARCHAR(11),
                `holder` TEXT,
                `descriptor` TEXT,
                `amount` FLOAT,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,

                PRIMARY KEY (`id`),
                KEY `fk.heidelpay_transfer_info.transaction_id` (`transaction_id`),

                CONSTRAINT `fk.heidelpay_transfer_info.transaction_id`
                    FOREIGN KEY (`transaction_id`)
                    REFERENCES `order_transaction` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        //Nothing to do
    }
}
