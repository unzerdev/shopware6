<?php
/**
 * This trait adds the state properties to a resource class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use RuntimeException;
use UnzerSDK\Constants\TransactionStatus;

trait HasStates
{
    /** @var bool $isError */
    private $isError = false;

    /** @var bool $isSuccess */
    private $isSuccess = false;

    /** @var bool $isPending */
    private $isPending = false;

    /** @var bool $isResumed */
    private $isResumed = false;

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return $this->isError;
    }

    /**
     * @param bool $isError
     *
     * @return self
     */
    protected function setIsError(bool $isError): self
    {
        $this->isError = $isError;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * @param bool $isSuccess
     *
     * @return self
     */
    protected function setIsSuccess(bool $isSuccess): self
    {
        $this->isSuccess = $isSuccess;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->isPending;
    }

    /**
     * @param bool $isPending
     *
     * @return self
     */
    protected function setIsPending(bool $isPending): self
    {
        $this->isPending = $isPending;
        return $this;
    }

    /**
     * @return bool
     */
    public function isResumed(): bool
    {
        return $this->isResumed;
    }

    /**
     * @param bool $isResumed
     *
     * @return self
     */
    public function setIsResumed(bool $isResumed): self
    {
        $this->isResumed = $isResumed;
        return $this;
    }

    /**
     * Map the 'status' that is used for transactions in the transaction list of a payment resource.
     * The actual transaction resource only has the isSuccess, isPending and isError property.
     *
     * @param string $status
     *
     * @throws RuntimeException
     *
     * @return self
     */
    protected function setStatus(string $status): self
    {
        $this->validateTransactionStatus($status);

        $this->setIsSuccess(false);
        $this->setIsPending(false);
        $this->setIsError(false);

        switch ($status) {
            case (TransactionStatus::STATUS_ERROR):
                $this->setIsError(true);
                break;
            case (TransactionStatus::STATUS_PENDING):
                $this->setIsPending(true);
                break;
            case (TransactionStatus::STATUS_SUCCESS):
                $this->setIsSuccess(true);
                break;
            case (TransactionStatus::STATUS_RESUMED):
                $this->setIsResumed(true);
                break;
        }

        return $this;
    }

    /**
     * Check if transaction status is valid. If status is invalid a RuntimeException is thrown
     *
     * @param string $status
     *
     * @throws RuntimeException
     */
    public function validateTransactionStatus(string $status): void
    {
        $validStatusArray = [
            TransactionStatus::STATUS_ERROR,
            TransactionStatus::STATUS_PENDING,
            TransactionStatus::STATUS_SUCCESS,
            TransactionStatus::STATUS_RESUMED,
        ];

        if (!in_array($status, $validStatusArray, true)) {
            throw new RuntimeException('Transaction status can not be set. Status is invalid for transaction.');
        }
    }
}
