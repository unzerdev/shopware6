<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1601201837RebrandingAdjustConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1601201837;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            UPDATE `system_config`
            SET `configuration_key` = REPLACE(`configuration_key`, 'HeidelPayment6', 'UnzerPayment6')
            WHERE `configuration_key` LIKE "HeidelPayment6.settings.%"
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        //Nothing to do
    }
}
