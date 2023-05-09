<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\BackwardsCompatibility;

use Doctrine\DBAL\Connection;

class DbalConnectionHelper
{
    /**
     * @return false|mixed
     */
    public static function fetchColumn(Connection $connection, string $query, array $params = [])
    {
        // TODO: Remove me if compatibility is at least 6.4.0.0
        if (!method_exists($connection, 'fetchOne')) {
            /** @phpstan-ignore-next-line */
            return $connection->fetchColumn($query, $params);
        }

        /** @phpstan-ignore-next-line */
        return $connection->fetchOne($query, $params);
    }

    public static function fetchAssoc(Connection $connection, string $query)
    {
        // TODO: Remove me if compatibility is at least 6.4.0.0
        if (!method_exists($connection, 'fetchAssociative')) {
            /** @phpstan-ignore-next-line */
            return $connection->fetchAssoc($query);
        }

        /** @phpstan-ignore-next-line */
        return $connection->fetchAssociative($query);
    }

    /**
     * @return int|string
     */
    public static function exec(Connection $connection, string $query)
    {
        // TODO: Remove me if compatibility is at least 6.4.0.0
        if (!method_exists($connection, 'executeStatement')) {
            return $connection->exec($query);
        }

        /** @phpstan-ignore-next-line */
        return $connection->executeStatement($query);
    }
}
