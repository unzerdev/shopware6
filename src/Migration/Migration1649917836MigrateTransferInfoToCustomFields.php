<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use UnzerPayment6\Installer\CustomFieldInstaller;

class Migration1649917836MigrateTransferInfoToCustomFields extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1649917836;
    }

    public function update(Connection $connection): void
    {
        $transferInfos = $connection->executeQuery('SELECT * FROM unzer_payment_transfer_info')->fetchAllAssociative();

        foreach ($transferInfos as $transferInfo) {
            $infoId        = $transferInfo['id'];
            $transactionId = $transferInfo['transaction_id'];

            unset(
                $transferInfo['id'],
                $transferInfo['transaction_id'],
                $transferInfo['transaction_version_id'],
                $transferInfo['created_at'],
                $transferInfo['updated_at']
            );

            $customFields = json_decode($connection->executeQuery('SELECT custom_fields FROM order_transaction WHERE id = ?', [$transactionId])->fetchOne(), true);

            if (!array_key_exists(CustomFieldInstaller::UNZER_PAYMENT_TRANSFER_INFO, $customFields)) {
                $customFields = array_merge($customFields, [CustomFieldInstaller::UNZER_PAYMENT_TRANSFER_INFO => $transferInfo]);

                if ($connection->executeStatement('UPDATE order_transaction SET custom_fields = ? WHERE id = ?', [json_encode($customFields), $transactionId]) !== 1) {
                    throw new \RuntimeException(sprintf('Can not migrate transfer info %s', Uuid::fromBytesToHex($infoId)));
                }
            }

            $connection->executeStatement('DELETE FROM unzer_payment_transfer_info WHERE id = ?', [$infoId]);
        }

        if ($connection->executeQuery('SELECT COUNT(*) FROM unzer_payment_transfer_info')->fetchOne() !== '0') {
            throw new \RuntimeException('Database table "unzer_payment_transfer_info" is not empty');
        }

        $connection->executeStatement('DROP TABLE unzer_payment_transfer_info');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
