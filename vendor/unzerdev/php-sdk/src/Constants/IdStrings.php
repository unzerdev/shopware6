<?php

namespace UnzerSDK\Constants;

/**
 * This file contains the different id strings to be handled within this SDK.
 *
 * @link  https://docs.unzer.com/
 */
class IdStrings
{
    // Transactions
    public const AUTHORIZE = 'aut';
    public const CANCEL = 'cnl';
    public const CHARGE = 'chg';
    public const PAYOUT = 'out';
    public const SHIPMENT = 'shp';
    public const CHARGEBACK = 'cbk';

    // Payment Types
    public const ALIPAY = 'ali';
    public const APPLEPAY = 'apl';
    public const BANCONTACT = 'bct';
    public const CARD = 'crd';
    public const EPS = 'eps';
    public const GIROPAY = 'gro';
    public const GOOGLE_PAY = 'gop';
    public const HIRE_PURCHASE_DIRECT_DEBIT = 'hdd';
    public const IDEAL = 'idl';
    public const INSTALLMENT_SECURED = 'ins';
    public const INVOICE = 'ivc';
    public const INVOICE_FACTORING = 'ivf';
    public const INVOICE_GUARANTEED = 'ivg';
    public const INVOICE_SECURED = 'ivs';
    public const KLARNA = 'kla';
    public const PAYLATER_DIRECT_DEBIT = 'pdd';
    public const PAYLATER_INVOICE = 'piv';
    public const PAYLATER_INSTALLMENT = 'pit';
    public const PAYMENT_PAGE = 'ppg';
    public const PAYPAL = 'ppl';
    public const PAYU = 'pyu';
    public const POST_FINANCE_CARD = 'pfc';
    public const POST_FINANCE_EFINANCE = 'pfe';
    public const PIS = 'pis';
    public const PREPAYMENT = 'ppy';
    public const PRZELEWY24 = 'p24';
    public const SEPA_DIRECT_DEBIT = 'sdd';
    public const SEPA_DIRECT_DEBIT_GUARANTEED = 'ddg';
    public const SEPA_DIRECT_DEBIT_SECURED = 'dds';
    public const SOFORT = 'sft';
    public const TWINT = 'twt';
    public const WECHATPAY = 'wcp';

    // Resources
    public const BASKET = 'bsk';
    public const CUSTOMER = 'cst';
    public const METADATA = 'mtd';
    public const PAYMENT = 'pay';

    public const WEBHOOK = 'whk';
    public const PAYMENT_TYPES = [
        self::ALIPAY,
        self::APPLEPAY,
        self::BANCONTACT,
        self::CARD,
        self::EPS,
        self::GIROPAY,
        self::GOOGLE_PAY,
        self::HIRE_PURCHASE_DIRECT_DEBIT,
        self::IDEAL,
        self::INSTALLMENT_SECURED,
        self::INVOICE,
        self::INVOICE_FACTORING,
        self::INVOICE_GUARANTEED,
        self::INVOICE_SECURED,
        self::KLARNA,
        self::PAYLATER_DIRECT_DEBIT,
        self::PAYLATER_INVOICE,
        self::PAYLATER_INSTALLMENT,
        self::PAYMENT_PAGE,
        self::PAYPAL,
        self::PAYU,
        self::POST_FINANCE_CARD,
        self::POST_FINANCE_EFINANCE,
        self::PIS,
        self::PREPAYMENT,
        self::PRZELEWY24,
        self::SEPA_DIRECT_DEBIT,
        self::SEPA_DIRECT_DEBIT_GUARANTEED,
        self::SEPA_DIRECT_DEBIT_SECURED,
        self::SOFORT,
        self::TWINT,
        self::WECHATPAY,
    ];
}
