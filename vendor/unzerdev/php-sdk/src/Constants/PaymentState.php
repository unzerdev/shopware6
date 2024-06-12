<?php

namespace UnzerSDK\Constants;

use RuntimeException;

/**
 * This file contains definitions of the payment states.
 *
 * @link  https://docs.unzer.com/
 *
 */
class PaymentState
{
    public const STATE_PENDING = 0;
    public const STATE_COMPLETED = 1;
    public const STATE_CANCELED = 2;
    public const STATE_PARTLY = 3;
    public const STATE_PAYMENT_REVIEW = 4;
    public const STATE_CHARGEBACK = 5;
    public const STATE_CREATE = 6;

    public const STATE_NAME_PENDING = 'pending';
    public const STATE_NAME_COMPLETED = 'completed';
    public const STATE_NAME_CANCELED = 'canceled';
    public const STATE_NAME_PARTLY = 'partly';
    public const STATE_NAME_PAYMENT_REVIEW = 'payment review';
    public const STATE_NAME_CHARGEBACK = 'chargeback';
    public const STATE_NAME_CREATE = 'create';

    /**
     * Returns the name of the state with the given code.
     *
     * @param int $stateCode The code of the payment state.
     *
     * @return string The name of the code.
     *
     * @throws RuntimeException A RuntimeException is thrown when the $stateCode is unknown.
     */
    public static function mapStateCodeToName(int $stateCode): string
    {
        switch ($stateCode) {
            case self::STATE_PENDING:
                $stateName =  self::STATE_NAME_PENDING;
                break;
            case self::STATE_COMPLETED:
                $stateName =  self::STATE_NAME_COMPLETED;
                break;
            case self::STATE_CANCELED:
                $stateName =  self::STATE_NAME_CANCELED;
                break;
            case self::STATE_PARTLY:
                $stateName =  self::STATE_NAME_PARTLY;
                break;
            case self::STATE_PAYMENT_REVIEW:
                $stateName =  self::STATE_NAME_PAYMENT_REVIEW;
                break;
            case self::STATE_CHARGEBACK:
                $stateName =  self::STATE_NAME_CHARGEBACK;
                break;
            case self::STATE_CREATE:
                $stateName =  self::STATE_NAME_CREATE;
                break;
            default:
                throw new RuntimeException('Unknown payment state #' . $stateCode);
        }

        return $stateName;
    }

    /**
     * Returns the name of the state with the given code.
     *
     * @param string $stateName The name of the code.
     *
     * @return int The code of the payment state.
     *
     * @throws RuntimeException A RuntimeException is thrown when the $stateName is unknown.
     */
    public static function mapStateNameToCode(string $stateName): int
    {
        switch ($stateName) {
            case self::STATE_NAME_PENDING:
                $stateCode = self::STATE_PENDING;
                break;
            case self::STATE_NAME_COMPLETED:
                $stateCode = self::STATE_COMPLETED;
                break;
            case self::STATE_NAME_CANCELED:
                $stateCode = self::STATE_CANCELED;
                break;
            case self::STATE_NAME_PARTLY:
                $stateCode = self::STATE_PARTLY;
                break;
            case self::STATE_NAME_PAYMENT_REVIEW:
                $stateCode = self::STATE_PAYMENT_REVIEW;
                break;
            case self::STATE_NAME_CHARGEBACK:
                $stateCode = self::STATE_CHARGEBACK;
                break;
            default:
                throw new RuntimeException('Unknown payment state ' . $stateName);
        }

        return $stateCode;
    }
}
