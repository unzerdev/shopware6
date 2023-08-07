<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1637594171OnDelete extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1637594171;
    }

    public function update(Connection $connection): void
    {
        $this->migrateOnDelete($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing to do
    }

    private function migrateOnDelete(Connection $connection): void
    {
        // first remove old fk
        $sql = 'ALTER TABLE unzer_payment_payment_device DROP FOREIGN KEY `fk.unzer_payment_payment_device.customer_id`;';

        $connection->executeStatement($sql);

        // second create new one
        $sql = 'ALTER TABLE unzer_payment_payment_device ADD CONSTRAINT `fk.unzer_payment_payment_device.customer_id` FOREIGN KEY (customer_id)
                REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;';

        $connection->executeStatement($sql);
    }
}
