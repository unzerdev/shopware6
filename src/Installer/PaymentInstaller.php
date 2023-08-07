<?php

declare(strict_types=1);

namespace UnzerPayment6\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use UnzerPayment6\Components\PaymentHandler\UnzerAlipayPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerApplePayPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerBancontactHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerCreditCardPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerDirectDebitPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerDirectDebitSecuredPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerEpsPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerGiropayPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerIdealPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerInstallmentSecuredPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerInvoicePaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerInvoiceSecuredPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPaylaterInvoicePaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPayPalPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPisPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPrePaymentPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPrzelewyHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerSofortPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerWeChatPaymentHandler;
use UnzerPayment6\UnzerPayment6;

class PaymentInstaller implements InstallerInterface
{
    public const PAYMENT_ID_ALIPAY               = 'bc4c2cbfb5fda0bf549e4807440d0a54';
    public const PAYMENT_ID_CREDIT_CARD          = '4673044aff79424a938d42e9847693c3';
    public const PAYMENT_ID_DIRECT_DEBIT         = '713c7a332b432dcd4092701eda522a7e';
    public const PAYMENT_ID_DIRECT_DEBIT_SECURED = '5123af5ce94a4a286641973e8de7eb60';
    public const PAYMENT_ID_EPS                  = '17830aa7e6a00b99eab27f0e45ac5e0d';
    public const PAYMENT_ID_FLEXIPAY             = '4ebb99451f36ba01f13d5871a30bce2c';
    public const PAYMENT_ID_GIROPAY              = 'd4b90a17af62c1bb2f6c3b1fed339425';
    public const PAYMENT_ID_INSTALLMENT_SECURED  = '4b9f8d08b46a83839fd0eb14fe00efe6';
    public const PAYMENT_ID_INVOICE              = '08fb8d9a72ab4ca62b811e74f2eca79f';
    public const PAYMENT_ID_INVOICE_SECURED      = '6cc3b56ce9b0f80bd44039c047282a41';
    public const PAYMENT_ID_IDEAL                = '614ad722a03ee96baa2446793143215b';
    public const PAYMENT_ID_PAYPAL               = '409fe641d6d62a4416edd6307d758791';
    public const PAYMENT_ID_PRE_PAYMENT          = '085b64d0028a8bd447294e03c4eb411a';
    public const PAYMENT_ID_PRZELEWY24           = 'cd6f59d572e6c90dff77a48ce16b44db';
    public const PAYMENT_ID_SOFORT               = '95aa098aac8f11e9a2a32a2ae2dbcce4';
    public const PAYMENT_ID_WE_CHAT              = 'fd96d03535a46d197f5adac17c9f8bac';
    public const PAYMENT_ID_BANCONTACT           = '87aa7a4e786c43ec9d4b9c1fd2aa51eb';
    public const PAYMENT_ID_PAYLATER_INVOICE     = '09588ffee8064f168e909ff31889dd7f';
    public const PAYMENT_ID_APPLE_PAY            = '62490bda54fa48fbb29ed6b9368bafe1';

    public const PAYMENT_METHOD_IDS = [
        self::PAYMENT_ID_ALIPAY,
        self::PAYMENT_ID_CREDIT_CARD,
        self::PAYMENT_ID_DIRECT_DEBIT,
        self::PAYMENT_ID_DIRECT_DEBIT_SECURED,
        self::PAYMENT_ID_EPS,
        self::PAYMENT_ID_FLEXIPAY,
        self::PAYMENT_ID_GIROPAY,
        self::PAYMENT_ID_INSTALLMENT_SECURED,
        self::PAYMENT_ID_INVOICE,
        self::PAYMENT_ID_INVOICE_SECURED,
        self::PAYMENT_ID_IDEAL,
        self::PAYMENT_ID_PAYPAL,
        self::PAYMENT_ID_PRE_PAYMENT,
        self::PAYMENT_ID_PRZELEWY24,
        self::PAYMENT_ID_SOFORT,
        self::PAYMENT_ID_WE_CHAT,
        self::PAYMENT_ID_BANCONTACT,
        self::PAYMENT_ID_PAYLATER_INVOICE,
        self::PAYMENT_ID_APPLE_PAY,
    ];

    public const PAYMENT_METHODS = [
        [
            'id'                => self::PAYMENT_ID_ALIPAY,
            'handlerIdentifier' => UnzerAlipayPaymentHandler::class,
            'name'              => 'Alipay (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Alipay (Unzer payments)',
                    'description' => 'Alipay Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Alipay (Unzer payments)',
                    'description' => 'Alipay payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_FLEXIPAY,
            'handlerIdentifier' => UnzerPisPaymentHandler::class,
            'name'              => 'Unzer bank transfer',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Unzer bank transfer',
                    'description' => 'Unzer bank transfer Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Unzer bank transfer',
                    'description' => 'Unzer bank transfer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_CREDIT_CARD,
            'handlerIdentifier' => UnzerCreditCardPaymentHandler::class,
            'name'              => 'Credit card (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Kreditkarte (Unzer payments)',
                    'description' => 'Kreditkartenzahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Credit card (Unzer payments)',
                    'description' => 'Credit card payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_EPS,
            'handlerIdentifier' => UnzerEpsPaymentHandler::class,
            'name'              => 'EPS (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'EPS (Unzer payments)',
                    'description' => 'EPS Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'EPS (Unzer payments)',
                    'description' => 'EPS payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_GIROPAY,
            'handlerIdentifier' => UnzerGiropayPaymentHandler::class,
            'name'              => 'Giropay (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Giropay (Unzer payments)',
                    'description' => 'Giropay Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Giropay (Unzer payments)',
                    'description' => 'Giropay payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_IDEAL,
            'handlerIdentifier' => UnzerIdealPaymentHandler::class,
            'name'              => 'iDEAL (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'iDEAL (Unzer payments)',
                    'description' => 'iDEAL Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'iDEAL (Unzer payments)',
                    'description' => 'iDEAL payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_INVOICE,
            'handlerIdentifier' => UnzerInvoicePaymentHandler::class,
            'name'              => 'Invoice (Unzer payments, deprecated)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Unzer invoice (veraltet)',
                    'description' => 'Rechnungskauf mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Unzer invoice (deprecated)',
                    'description' => 'Invoice payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_INVOICE_SECURED,
            'handlerIdentifier' => UnzerInvoiceSecuredPaymentHandler::class,
            'name'              => 'Unzer invoice secured (deprecated)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Unzer invoice secured (veraltet)',
                    'description' => 'Gesicherter Rechnungskauf mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Unzer invoice secured (deprecated)',
                    'description' => 'Invoice secured payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_INSTALLMENT_SECURED,
            'handlerIdentifier' => UnzerInstallmentSecuredPaymentHandler::class,
            'name'              => 'Unzer Installment',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Unzer Ratenzahlung',
                    'description' => 'Unzer Ratenzahlung',
                ],
                'en-GB' => [
                    'name'        => 'Unzer Installment',
                    'description' => 'Unzer Installment',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_PAYPAL,
            'handlerIdentifier' => UnzerPayPalPaymentHandler::class,
            'name'              => 'PayPal (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'PayPal (Unzer payments)',
                    'description' => 'PayPal Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'PayPal (Unzer payments)',
                    'description' => 'PayPal payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_PRE_PAYMENT,
            'handlerIdentifier' => UnzerPrePaymentPaymentHandler::class,
            'name'              => 'Prepayment (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Vorkasse (Unzer payments)',
                    'description' => 'Zahlung auf Vorkasse mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Prepayment (Unzer payments)',
                    'description' => 'Prepayment with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_PRZELEWY24,
            'handlerIdentifier' => UnzerPrzelewyHandler::class,
            'name'              => 'Przelewy24 (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Przelewy24 (Unzer payments)',
                    'description' => 'Przelewy24 Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Przelewy24 (Unzer payments)',
                    'description' => 'Przelewy24 payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_DIRECT_DEBIT,
            'handlerIdentifier' => UnzerDirectDebitPaymentHandler::class,
            'name'              => 'SEPA direct debit (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'SEPA Lastschrift (Unzer payments)',
                    'description' => 'SEPA Lastschrift Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'SEPA direct debit (Unzer payments)',
                    'description' => 'SEPA direct debit payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_DIRECT_DEBIT_SECURED,
            'handlerIdentifier' => UnzerDirectDebitSecuredPaymentHandler::class,
            'name'              => 'SEPA direct debit secured (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'SEPA Lastschrift gesichert (Unzer payments)',
                    'description' => 'Gesicherte SEPA Lastschrift Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'SEPA direct debit secured (Unzer payments)',
                    'description' => 'Secured SEPA direct debit payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_SOFORT,
            'handlerIdentifier' => UnzerSofortPaymentHandler::class,
            'name'              => 'Sofort (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Sofort (Unzer payments)',
                    'description' => 'Sofort mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Sofort (Unzer payments)',
                    'description' => 'Sofort with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_WE_CHAT,
            'handlerIdentifier' => UnzerWeChatPaymentHandler::class,
            'name'              => 'WeChat (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'WeChat (Unzer payments)',
                    'description' => 'WeChat Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'WeChat (Unzer payments)',
                    'description' => 'WeChat payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_BANCONTACT,
            'handlerIdentifier' => UnzerBancontactHandler::class,
            'name'              => 'Bancontact (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Bancontact (Unzer payments)',
                    'description' => 'Bancontact Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Bancontact (Unzer payments)',
                    'description' => 'Bancontact payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_PAYLATER_INVOICE,
            'handlerIdentifier' => UnzerPaylaterInvoicePaymentHandler::class,
            'name'              => 'Invoice (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Unzer invoice',
                    'description' => 'Rechnungskauf mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Unzer invoice',
                    'description' => 'Invoice payments with Unzer payments',
                ],
            ],
        ],
        [
            'id'                => self::PAYMENT_ID_APPLE_PAY,
            'handlerIdentifier' => UnzerApplePayPaymentHandler::class,
            'name'              => 'Apple Pay (Unzer payments)',
            'translations'      => [
                'de-DE' => [
                    'name'        => 'Apple Pay (Unzer payments)',
                    'description' => 'Apple Pay mit Unzer payments',
                ],
                'en-GB' => [
                    'name'        => 'Apple Pay (Unzer payments)',
                    'description' => 'Apple Pay with Unzer payments',
                ],
            ],
        ],
    ];
    private const PLUGIN_VERSION_PAYLATER_INVOICE = '5.0.0';

    // TODO: Adjust this if compatibility is at least 6.5.0.0
    /** @var EntityRepository|\Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator */
    private $paymentMethodRepository;

    private PluginIdProvider $pluginIdProvider;

    // TODO: Adjust this if compatibility is at least 6.5.0.0

    /**
     * @param EntityRepository|\Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator $paymentMethodRepository
     */
    public function __construct($paymentMethodRepository, PluginIdProvider $pluginIdProvider)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->pluginIdProvider        = $pluginIdProvider;
    }

    public function install(InstallContext $context): void
    {
        $this->upsertPaymentMethods($context);
    }

    public function update(UpdateContext $context): void
    {
        $this->upsertPaymentMethods($context);

        if ($context->getUpdatePluginVersion() === self::PLUGIN_VERSION_PAYLATER_INVOICE) {
            $this->paymentMethodRepository->upsert([
                $this->getPaymentMethod(self::PAYMENT_ID_INVOICE),
                $this->getPaymentMethod(self::PAYMENT_ID_INVOICE_SECURED),
            ], $context->getContext());

            // Make sure every Unzer payment method is linked to the plugin
            $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(UnzerPayment6::class, $context->getContext());
            $update   = array_map(static function ($paymentMethod) use ($pluginId) {
                return [
                    'pluginId' => $pluginId,
                    'id'       => $paymentMethod['id'],
                ];
            }, self::PAYMENT_METHODS);
            $this->paymentMethodRepository->upsert($update, $context->getContext());
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->setAllPaymentMethodsActive(false, $context);
    }

    public function activate(ActivateContext $context): void
    {
        $this->setAllPaymentMethodsActive(true, $context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->setAllPaymentMethodsActive(false, $context);
    }

    private function upsertPaymentMethods(InstallContext $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(UnzerPayment6::class, $context->getContext());

        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if (!$this->isPaymentMethodInstalled($paymentMethod['id'], $context->getContext())) {
                $paymentMethod['pluginId'] = $pluginId;

                $this->paymentMethodRepository->upsert([$paymentMethod], $context->getContext());
            }
        }
    }

    private function setAllPaymentMethodsActive(bool $active, InstallContext $context): void
    {
        $upsertPayload = [];
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $paymentMethodCriteria = new Criteria([$paymentMethod['id']]);
            $hasPaymentMethod      = $this->paymentMethodRepository->searchIds($paymentMethodCriteria, $context->getContext())->getTotal() > 0;

            if (!$hasPaymentMethod) {
                continue;
            }

            $upsertPayload[] = [
                'id'     => $paymentMethod['id'],
                'active' => $active,
            ];
        }

        $this->paymentMethodRepository->upsert($upsertPayload, $context->getContext());
    }

    private function isPaymentMethodInstalled(string $paymentMethodId, Context $context): bool
    {
        return $this->paymentMethodRepository->searchIds(new Criteria([$paymentMethodId]), $context)->getTotal() > 0;
    }

    private function getPaymentMethod(string $paymentMethodId): ?array
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if ($paymentMethod['id'] === $paymentMethodId) {
                return $paymentMethod;
            }
        }

        return null;
    }
}
