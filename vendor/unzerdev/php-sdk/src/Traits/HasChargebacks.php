<?php
/**
 * This trait adds the cancellation property to a class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Interfaces\UnzerParentInterface;
use UnzerSDK\Resources\AbstractUnzerResource;
use RuntimeException;
use UnzerSDK\Resources\TransactionTypes\Chargeback;

trait HasChargebacks
{
    /** @var Chargeback[] $chargebacks */
    private $chargebacks = [];

    /**
     * @return array
     */
    public function getChargebacks(): array
    {
        return $this->chargebacks;
    }

    /**
     * @param array $chargebacks
     *
     * @return self
     */
    public function setChargebacks(array $chargebacks): self
    {
        $this->chargebacks = $chargebacks;
        return $this;
    }

    /**
     * @param Chargeback $chargeback
     *
     * @return self
     */
    public function addChargeback(Chargeback $chargeback): self
    {
        if ($this instanceof UnzerParentInterface) {
            $chargeback->setParentResource($this);
        }
        $this->chargebacks[] = $chargeback;
        return $this;
    }

    /**
     * Return specific Chargeback object or null if it does not exist.
     *
     * @param string  $chargebackId The id of the chargeback object
     * @param boolean $lazy
     *
     * @return Chargeback|null The chargeback or null if none could be found.
     *
     * @throws RuntimeException  A RuntimeException is thrown when there is an error while using the SDK.
     * @throws UnzerApiException An UnzerApiException is thrown if there is an error returned on API-request.
     */
    public function getChargeback(string $chargebackId, bool $lazy = false): ?Chargeback
    {
        /** @var Chargeback $chargeback */
        foreach ($this->chargebacks as $chargeback) {
            if ($chargeback->getId() === $chargebackId) {
                if (!$lazy && $this instanceof UnzerParentInterface) {
                    /** @var AbstractUnzerResource $this*/
                    $this->getResource($chargeback);
                }
                return $chargeback;
            }
        }
        return null;
    }
}
