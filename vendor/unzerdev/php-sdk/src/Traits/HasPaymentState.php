<?php
/**
 * This trait adds the state property to a class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use UnzerSDK\Constants\PaymentState;
use RuntimeException;

trait HasPaymentState
{
    /** @var int */
    private $state = 0;

    /**
     * Return true if the state is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->getState() === PaymentState::STATE_PENDING;
    }

    /**
     * Return true if the state is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->getState() === PaymentState::STATE_COMPLETED;
    }

    /**
     * Return true if the state is canceled.
     *
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->getState() === PaymentState::STATE_CANCELED;
    }

    /**
     * Return true if the state is partly paid.
     *
     * @return bool
     */
    public function isPartlyPaid(): bool
    {
        return $this->getState() === PaymentState::STATE_PARTLY;
    }

    /**
     * Return true if the state is payment review.
     *
     * @return bool
     */
    public function isPaymentReview(): bool
    {
        return $this->getState() === PaymentState::STATE_PAYMENT_REVIEW;
    }

    /**
     * Return true if the state is chargeback.
     *
     * @return bool
     */
    public function isChargeBack(): bool
    {
        return $this->getState() === PaymentState::STATE_CHARGEBACK;
    }

    /**
     * Return true if the state is create.
     *
     * @return bool
     */
    public function isCreate(): bool
    {
        return $this->getState() === PaymentState::STATE_CREATE;
    }

    /**
     * Returns the current state code (ref. Constants/PaymentState).
     *
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Returns the current state code (ref. Constants/PaymentState).
     *
     * @return string The name of the current payment state.
     *
     * @throws RuntimeException
     */
    public function getStateName(): string
    {
        return PaymentState::mapStateCodeToName($this->state);
    }

    /**
     * Sets the current state.
     *
     * @param int $state
     *
     * @return self
     */
    protected function setState(int $state): self
    {
        $this->state = $state;
        return $this;
    }
}
