<?php

namespace UnzerSDK\Constants;

/**
 * This file contains definitions of the available transaction types.
 *
 * @link  https://docs.unzer.com/
 *
 */
class TransactionTypes
{
    public const AUTHORIZATION = 'authorize';
    public const CHARGE = 'charge';
    public const REVERSAL = 'cancel-authorize';
    public const REFUND = 'cancel-charge';
    public const SHIPMENT = 'shipment';
    public const PAYOUT = 'payout';
    public const CHARGEBACK = 'chargeback';
}
