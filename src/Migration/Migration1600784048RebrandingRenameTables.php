<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use UnzerPayment6\Components\BackwardsCompatibility\DbalConnectionHelper;

class Migration1600784048RebrandingRenameTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600784048;
    }

    public function update(Connection $connection): void
    {
        $transferResult = DbalConnectionHelper::fetchColumn($connection, 'SHOW TABLES LIKE \'unzer_payment_transfer_info\';');

        if (!$transferResult) {
            DbalConnectionHelper::exec($connection,'RENAME TABLE heidelpay_transfer_info TO unzer_payment_transfer_info;');
        }

        $deviceResult = DbalConnectionHelper::fetchColumn($connection, 'SHOW TABLES LIKE \'unzer_payment_payment_device\';');

        if (!$deviceResult) {
            DbalConnectionHelper::exec($connection, 'RENAME TABLE heidelpay_payment_device TO unzer_payment_payment_device;');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        //Nothing to do
    }
}
