<?php

namespace UnzerSDK\Constants;

/**
 * This file contains the different web hook events which can be subscribed.
 *
 * @link  https://docs.unzer.com/
 *
 */
class WebhookEvents
{
    // all events
    public const ALL = 'all';

    // authorize events
    public const AUTHORIZE = 'authorize';
    public const AUTHORIZE_CANCELED = 'authorize.canceled';
    public const AUTHORIZE_EXPIRED = 'authorize.expired';
    public const AUTHORIZE_FAILED = 'authorize.failed';
    public const AUTHORIZE_PENDING = 'authorize.pending';
    public const AUTHORIZE_RESUMED = 'authorize.resumed';
    public const AUTHORIZE_SUCCEEDED = 'authorize.succeeded';

    // preauthorize events
    public const PREAUTHORIZE = 'preauthorize';
    public const PREAUTHORIZE_CANCELED = 'preauthorize.canceled';
    public const PREAUTHORIZE_EXPIRED = 'preauthorize.expired';
    public const PREAUTHORIZE_FAILED = 'preauthorize.failed';
    public const PREAUTHORIZE_PENDING = 'preauthorize.pending';
    public const PREAUTHORIZE_RESUMED = 'preauthorize.resumed';
    public const PREAUTHORIZE_SUCCEEDED = 'preauthorize.succeeded';

    // charge events
    public const CHARGE = 'charge';
    public const CHARGE_CANCELED = 'charge.canceled';
    public const CHARGE_EXPIRED = 'charge.expired';
    public const CHARGE_FAILED = 'charge.failed';
    public const CHARGE_PENDING = 'charge.pending';
    public const CHARGE_RESUMED = 'charge.resumed';
    public const CHARGE_SUCCEEDED = 'charge.succeeded';

    // chargeback events
    public const CHARGEBACK = 'chargeback';

    // payout events
    public const PAYOUT = 'payout';
    public const PAYOUT_SUCCEEDED = 'payout.succeeded';
    public const PAYOUT_FAILED = 'payout.failed';

    // types events
    public const TYPES = 'types';

    // customer events
    public const CUSTOMER = 'customer';
    public const CUSTOMER_CREATED = 'customer.created';
    public const CUSTOMER_DELETED = 'customer.deleted';
    public const CUSTOMER_UPDATED = 'customer.updated';

    // payment events
    public const PAYMENT = 'payment';
    public const PAYMENT_PENDING = 'payment.pending';
    public const PAYMENT_COMPLETED = 'payment.completed';
    public const PAYMENT_CANCELED = 'payment.canceled';
    public const PAYMENT_PARTLY = 'payment.partly';
    public const PAYMENT_PAYMENT_REVIEW = 'payment.payment_review';
    public const PAYMENT_CHARGEBACK = 'payment.chargeback';

    // shipment events
    public const SHIPMENT = 'shipment';

    public const ALLOWED_WEBHOOKS = [
        self::ALL,
        self::AUTHORIZE,
        self::AUTHORIZE_CANCELED,
        self::AUTHORIZE_EXPIRED,
        self::AUTHORIZE_FAILED,
        self::AUTHORIZE_PENDING,
        self::AUTHORIZE_RESUMED,
        self::AUTHORIZE_SUCCEEDED,
        self::PREAUTHORIZE,
        self::PREAUTHORIZE_CANCELED,
        self::PREAUTHORIZE_EXPIRED,
        self::PREAUTHORIZE_FAILED,
        self::PREAUTHORIZE_PENDING,
        self::PREAUTHORIZE_RESUMED,
        self::PREAUTHORIZE_SUCCEEDED,
        self::CHARGE,
        self::CHARGE_CANCELED,
        self::CHARGE_EXPIRED,
        self::CHARGE_FAILED,
        self::CHARGE_PENDING,
        self::CHARGE_RESUMED,
        self::CHARGE_SUCCEEDED,
        self::CHARGEBACK,
        self::PAYOUT,
        self::PAYOUT_SUCCEEDED,
        self::PAYOUT_FAILED,
        self::TYPES,
        self::CUSTOMER,
        self::CUSTOMER_CREATED,
        self::CUSTOMER_DELETED,
        self::CUSTOMER_UPDATED,
        self::PAYMENT,
        self::PAYMENT_PENDING,
        self::PAYMENT_COMPLETED,
        self::PAYMENT_CANCELED,
        self::PAYMENT_PARTLY,
        self::PAYMENT_PAYMENT_REVIEW,
        self::PAYMENT_CHARGEBACK,
        self::SHIPMENT
    ];
}
