<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\PaymentActions\Struct;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @extends Collection<RefundItem>
 *
 * @method void add(RefundItem $entity)
 * @method void set(string $key, RefundItem $entity)
 * @method RefundItem[] getIterator()
 * @method RefundItem[] getElements()
 * @method RefundItem|null get(string $key)
 * @method RefundItem|null first()
 * @method RefundItem|null last()
 */
class RefundItemCollection extends Collection
{
    public static function fromArray(array $items): self
    {
        $collection = new self();

        foreach ($items as $item) {
            $collection->add(RefundItem::fromArray($item));
        }

        return $collection;
    }

    public function jsonSerialize(): array
    {
        $return = [];
        foreach ($this->getElements() as $item) {
            $return[] = $item->jsonSerialize();
        }

        return $return;
    }

    protected function getExpectedClass(): ?string
    {
        return RefundItem::class;
    }
}
