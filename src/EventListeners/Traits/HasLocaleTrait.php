<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Traits;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;

trait HasLocaleTrait
{
    private function getLocaleByLanguageId(string $languageId, Context $context): string
    {
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity|null $searchResult */
        $searchResult = $this->languageRepository->search($criteria, $context)->first();

        if ($searchResult === null || $searchResult->getLocale() === null) {
            return ClientFactoryInterface::DEFAULT_LOCALE;
        }

        return $searchResult->getLocale()->getCode();
    }
}
