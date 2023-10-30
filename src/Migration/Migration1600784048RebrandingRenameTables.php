<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1600784048RebrandingRenameTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600784048;
    }

    public function update(Connection $connection): void
    {
        $transferResult = $connection->fetchOne('SHOW TABLES LIKE \'unzer_payment_transfer_info\';');

        if (!$transferResult) {
            $connection->executeStatement('RENAME TABLE heidelpay_transfer_info TO unzer_payment_transfer_info;');
        }

        $deviceResult = $connection->fetchOne('SHOW TABLES LIKE \'unzer_payment_payment_device\';');

        if (!$deviceResult) {
            $connection->executeStatement('RENAME TABLE heidelpay_payment_device TO unzer_payment_payment_device;');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        //Nothing to do
    }
}
