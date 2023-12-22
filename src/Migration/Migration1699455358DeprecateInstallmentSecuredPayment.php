<?php

declare(strict_types=1);

namespace UnzerPayment6\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Shopware\Core\Framework\Migration\MigrationStep;
use UnzerPayment6\Installer\PaymentInstaller;

class Migration1699455358DeprecateInstallmentSecuredPayment extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1699455358;
    }

    public function update(Connection $connection): void
    {
        $this->deactivePaymentMethod($connection);
        $this->appendDeprecationToPaymentMethodName($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function deactivePaymentMethod(Connection $connection): void
    {
        $sql = <<<SQL
            UPDATE `payment_method`
            SET `active` = 0
            WHERE `id` = UNHEX(:id)
SQL;

        $connection->executeStatement(
            $sql,
            ['id' => PaymentInstaller::PAYMENT_ID_INSTALLMENT_SECURED],
            ['id' => ParameterType::STRING]
        );
    }

    private function appendDeprecationToPaymentMethodName(Connection $connection): void
    {
        $this->appendDeprecationToPaymentMethodNameInGerman($connection);
        $this->appendDeprecationToPaymentMethodNameInEnglish($connection);
    }

    private function appendDeprecationToPaymentMethodNameInGerman(Connection $connection): void
    {
        $result = $connection->createQueryBuilder()
            ->select('lang.id')
            ->from('language', 'lang')
            ->innerJoin('lang', 'locale', 'loc', 'lang.translation_code_id = loc.id')
            ->where('loc.code LIKE :germanLanguagePart')
            ->setParameter('germanLanguagePart', 'de-%')
            ->execute();

        if (!$result instanceof Result) {
            return;
        }

        $germanLanguageIds = $result->fetchFirstColumn();

        $sql = <<<SQL
            UPDATE `payment_method_translation`
            SET `name` = 'Unzer Ratenzahlung (veraltet)'
            WHERE `payment_method_id` = HEX(:id) AND `language_id` IN (:languageIds)
SQL;

        $connection->executeStatement(
            $sql,
            ['id' => PaymentInstaller::PAYMENT_ID_INSTALLMENT_SECURED, 'languageIds' => $germanLanguageIds],
            ['id' => ParameterType::STRING, 'languageIds' => Connection::PARAM_STR_ARRAY]
        );
    }

    private function appendDeprecationToPaymentMethodNameInEnglish(Connection $connection): void
    {
        $result = $connection->createQueryBuilder()
            ->select('lang.id')
            ->from('language', 'lang')
            ->innerJoin('lang', 'locale', 'loc', 'lang.translation_code_id = loc.id')
            ->where('loc.code LIKE :englishLanguagePart')
            ->setParameter('englishLanguagePart', 'en-%')
            ->execute();

        if (!$result instanceof Result) {
            return;
        }

        $englishLanguageIds = $result->fetchFirstColumn();

        $sql = <<<SQL
            UPDATE `payment_method_translation`
            SET `name` = 'Unzer Installment (deprecated)'
            WHERE `payment_method_id` = HEX(:id) AND `language_id` IN (:languageIds)
SQL;

        $connection->executeStatement(
            $sql,
            ['id' => PaymentInstaller::PAYMENT_ID_INSTALLMENT_SECURED, 'languageIds' => $englishLanguageIds],
            ['id' => ParameterType::STRING, 'languageIds' => Connection::PARAM_STR_ARRAY]
        );
    }
}
