<?php

declare(strict_types=1);

namespace UnzerPayment6\Installer;

use League\Flysystem\Filesystem;
use Shopware\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
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
use UnzerPayment6\Components\PaymentHandler\UnzerApplePayV2PaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerBancontactHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerCreditCardPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerDirectDebitPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerEpsPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerGooglePayPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerIdealPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerKlarnaPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerOpenBankingPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPaylaterDirectDebitSecuredPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPaylaterInstallmentPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPaylaterInvoicePaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPayPalPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPrePaymentPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerPrzelewyHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerTwintPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerWeChatPaymentHandler;
use UnzerPayment6\Components\PaymentHandler\UnzerWeroPaymentHandler;
use UnzerPayment6\UnzerPayment6;

class PaymentInstaller implements InstallerInterface
{
    public const PAYMENT_ID_ALIPAY = 'bc4c2cbfb5fda0bf549e4807440d0a54';
    public const PAYMENT_ID_CREDIT_CARD = '4673044aff79424a938d42e9847693c3';
    public const PAYMENT_ID_DIRECT_DEBIT = '713c7a332b432dcd4092701eda522a7e';
    public const PAYMENT_ID_DIRECT_DEBIT_SECURED = '5123af5ce94a4a286641973e8de7eb60';
    public const PAYMENT_ID_EPS = '17830aa7e6a00b99eab27f0e45ac5e0d';
    public const PAYMENT_ID_FLEXIPAY = '4ebb99451f36ba01f13d5871a30bce2c';
    public const PAYMENT_ID_GIROPAY = 'd4b90a17af62c1bb2f6c3b1fed339425';
    public const PAYMENT_ID_INSTALLMENT_SECURED = '4b9f8d08b46a83839fd0eb14fe00efe6';
    public const PAYMENT_ID_INVOICE = '08fb8d9a72ab4ca62b811e74f2eca79f';
    public const PAYMENT_ID_INVOICE_SECURED = '6cc3b56ce9b0f80bd44039c047282a41';
    public const PAYMENT_ID_IDEAL = '614ad722a03ee96baa2446793143215b';
    public const PAYMENT_ID_PAYPAL = '409fe641d6d62a4416edd6307d758791';
    public const PAYMENT_ID_PRE_PAYMENT = '085b64d0028a8bd447294e03c4eb411a';
    public const PAYMENT_ID_PRZELEWY24 = 'cd6f59d572e6c90dff77a48ce16b44db';
    public const PAYMENT_ID_SOFORT = '95aa098aac8f11e9a2a32a2ae2dbcce4';
    public const PAYMENT_ID_WE_CHAT = 'fd96d03535a46d197f5adac17c9f8bac';
    public const PAYMENT_ID_BANCONTACT = '87aa7a4e786c43ec9d4b9c1fd2aa51eb';
    public const PAYMENT_ID_PAYLATER_INVOICE = '09588ffee8064f168e909ff31889dd7f';
    public const PAYMENT_ID_APPLE_PAY = '62490bda54fa48fbb29ed6b9368bafe1';
    public const PAYMENT_ID_APPLE_PAY_V2 = '55d6b81aa84911ef948a2f39e097a1d7';
    public const PAYMENT_ID_PAYLATER_INSTALLMENT = '12fbfbce271a43a89b3783453b88e9a6';
    public const PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED = '6d6adcd4b7bf40499873c294a85f32ed';
    public const PAYMENT_ID_GOOGLE_PAY = '67b6d50c1ecd11ef9e21d7850819bc50';
    public const PAYMENT_ID_TWINT = '6493b43244eb11efa900b7a80e209d6a';
    public const PAYMENT_ID_OPEN_BANKING = '105932c2e56b11ef9cd003e762195b4d';
    public const PAYMENT_ID_KLARNA = '21e0875e4d5b11f09349578ade9d62d8';
    public const PAYMENT_ID_WERO = 'e9d326149a3f11f082ea6b21179cd920';

    public const PAYMENT_METHOD_IDS = [
        self::PAYMENT_ID_ALIPAY,
        self::PAYMENT_ID_CREDIT_CARD,
        self::PAYMENT_ID_DIRECT_DEBIT,
        self::PAYMENT_ID_EPS,
        self::PAYMENT_ID_IDEAL,
        self::PAYMENT_ID_PAYPAL,
        self::PAYMENT_ID_PRE_PAYMENT,
        self::PAYMENT_ID_PRZELEWY24,
        self::PAYMENT_ID_WE_CHAT,
        self::PAYMENT_ID_BANCONTACT,
        self::PAYMENT_ID_PAYLATER_INVOICE,
        self::PAYMENT_ID_APPLE_PAY_V2,
        self::PAYMENT_ID_PAYLATER_INSTALLMENT,
        self::PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED,
        self::PAYMENT_ID_GOOGLE_PAY,
        self::PAYMENT_ID_TWINT,
        self::PAYMENT_ID_OPEN_BANKING,
        self::PAYMENT_ID_KLARNA,
        self::PAYMENT_ID_WERO,
    ];

    public const DEPRECATED_PAYMENT_METHOD_IDS = [];

    public const REMOVED_PAYMENT_METHOD_IDS = [
        self::PAYMENT_ID_FLEXIPAY,
        self::PAYMENT_ID_GIROPAY,
        self::PAYMENT_ID_INVOICE,
        self::PAYMENT_ID_INVOICE_SECURED,
        self::PAYMENT_ID_INSTALLMENT_SECURED,
        self::PAYMENT_ID_DIRECT_DEBIT_SECURED,
        self::PAYMENT_ID_APPLE_PAY,
        self::PAYMENT_ID_SOFORT,
    ];

    public const PAYMENT_METHODS = [
        [
            'id' => self::PAYMENT_ID_ALIPAY,
            'handlerIdentifier' => UnzerAlipayPaymentHandler::class,
            'name' => 'Alipay',
            'technicalName' => 'unzer_alipay',
            'translations' => [
                'de-DE' => [
                    'name' => 'Alipay',
                    'description' => 'Alipay Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Alipay',
                    'description' => 'Alipay payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_CREDIT_CARD,
            'handlerIdentifier' => UnzerCreditCardPaymentHandler::class,
            'name' => 'Credit card',
            'technicalName' => 'unzer_creditcard',
            'translations' => [
                'de-DE' => [
                    'name' => 'Kreditkarte',
                    'description' => 'Kreditkartenzahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Credit card',
                    'description' => 'Credit card payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_EPS,
            'handlerIdentifier' => UnzerEpsPaymentHandler::class,
            'name' => 'EPS',
            'technicalName' => 'unzer_eps',
            'translations' => [
                'de-DE' => [
                    'name' => 'EPS',
                    'description' => 'EPS Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'EPS',
                    'description' => 'EPS payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_IDEAL,
            'handlerIdentifier' => UnzerIdealPaymentHandler::class,
            'name' => 'iDEAL | Wero',
            'technicalName' => 'unzer_ideal',
            'translations' => [
                'de-DE' => [
                    'name' => 'iDEAL | Wero',
                    'description' => 'iDEAL | Wero Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'iDEAL | Wero',
                    'description' => 'iDEAL | Wero payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_PAYLATER_INSTALLMENT,
            'handlerIdentifier' => UnzerPaylaterInstallmentPaymentHandler::class,
            'name' => 'Installment',
            'technicalName' => 'unzer_installment',
            'translations' => [
                'de-DE' => [
                    'name' => 'Ratenzahlung',
                    'description' => 'Ratenzahlung mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Installment',
                    'description' => 'Installment with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_PAYLATER_DIRECT_DEBIT_SECURED,
            'handlerIdentifier' => UnzerPaylaterDirectDebitSecuredPaymentHandler::class,
            'name' => 'Direct Debit',
            'technicalName' => 'unzer_directdebitsecured',
            'translations' => [
                'de-DE' => [
                    'name' => 'Lastschrift',
                    'description' => 'Unzer Lastschrift',
                ],
                'en-GB' => [
                    'name' => 'Direct Debit',
                    'description' => 'Unzer Direct Debit',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_PAYPAL,
            'handlerIdentifier' => UnzerPayPalPaymentHandler::class,
            'name' => 'PayPal',
            'technicalName' => 'unzer_paypal',
            'translations' => [
                'de-DE' => [
                    'name' => 'PayPal',
                    'description' => 'PayPal Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'PayPal',
                    'description' => 'PayPal payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_PRE_PAYMENT,
            'handlerIdentifier' => UnzerPrePaymentPaymentHandler::class,
            'name' => 'Prepayment',
            'technicalName' => 'unzer_prepayment',
            'translations' => [
                'de-DE' => [
                    'name' => 'Vorkasse',
                    'description' => 'Zahlung auf Vorkasse mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Prepayment',
                    'description' => 'Prepayment with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_PRZELEWY24,
            'handlerIdentifier' => UnzerPrzelewyHandler::class,
            'name' => 'Przelewy24',
            'technicalName' => 'unzer_przelewy24',
            'translations' => [
                'de-DE' => [
                    'name' => 'Przelewy24',
                    'description' => 'Przelewy24 Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Przelewy24',
                    'description' => 'Przelewy24 payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_DIRECT_DEBIT,
            'handlerIdentifier' => UnzerDirectDebitPaymentHandler::class,
            'name' => 'SEPA Direct Debit',
            'technicalName' => 'unzer_directdebit',
            'translations' => [
                'de-DE' => [
                    'name' => 'SEPA Lastschrift',
                    'description' => 'SEPA Lastschrift Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'SEPA Direct Debit',
                    'description' => 'SEPA Direct Debit payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_WE_CHAT,
            'handlerIdentifier' => UnzerWeChatPaymentHandler::class,
            'name' => 'WeChat Pay',
            'technicalName' => 'unzer_wechatpay',
            'translations' => [
                'de-DE' => [
                    'name' => 'WeChat Pay',
                    'description' => 'WeChat Pay Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'WeChat Pay',
                    'description' => 'WeChat Pay payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_BANCONTACT,
            'handlerIdentifier' => UnzerBancontactHandler::class,
            'name' => 'Bancontact',
            'technicalName' => 'unzer_bancontact',
            'translations' => [
                'de-DE' => [
                    'name' => 'Bancontact',
                    'description' => 'Bancontact Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Bancontact',
                    'description' => 'Bancontact payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_PAYLATER_INVOICE,
            'handlerIdentifier' => UnzerPaylaterInvoicePaymentHandler::class,
            'name' => 'Invoice',
            'technicalName' => 'unzer_invoice',
            'translations' => [
                'de-DE' => [
                    'name' => 'Rechnung',
                    'description' => 'Zahlung auf Rechnung mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Invoice',
                    'description' => 'Invoice payments with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_APPLE_PAY_V2,
            'handlerIdentifier' => UnzerApplePayV2PaymentHandler::class,
            'name' => 'Apple Pay',
            'technicalName' => 'unzer_applepay_v2',
            'translations' => [
                'de-DE' => [
                    'name' => 'Apple Pay',
                    'description' => 'Apple Pay mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Apple Pay',
                    'description' => 'Apple Pay with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_GOOGLE_PAY,
            'handlerIdentifier' => UnzerGooglePayPaymentHandler::class,
            'name' => 'Google Pay',
            'technicalName' => 'unzer_googlepay',
            'translations' => [
                'de-DE' => [
                    'name' => 'Google Pay',
                    'description' => 'Google Pay mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Google Pay',
                    'description' => 'Google Pay with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_TWINT,
            'handlerIdentifier' => UnzerTwintPaymentHandler::class,
            'name' => 'TWINT',
            'technicalName' => 'unzer_twint',
            'translations' => [
                'de-DE' => [
                    'name' => 'TWINT',
                    'description' => 'TWINT mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'TWINT',
                    'description' => 'TWINT with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_OPEN_BANKING,
            'handlerIdentifier' => UnzerOpenBankingPaymentHandler::class,
            'name' => 'TWINT',
            'technicalName' => 'unzer_openbanking',
            'translations' => [
                'de-DE' => [
                    'name' => 'Direktüberweisung',
                    'description' => 'Direktüberweisung mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Direct Bank Transfer',
                    'description' => 'Direct Bank Transfer with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_KLARNA,
            'handlerIdentifier' => UnzerKlarnaPaymentHandler::class,
            'name' => 'TWINT',
            'technicalName' => 'unzer_klarna',
            'translations' => [
                'de-DE' => [
                    'name' => 'Klarna',
                    'description' => 'Klarna mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Klarna',
                    'description' => 'Klarna with Unzer payments',
                ],
            ],
        ],
        [
            'id' => self::PAYMENT_ID_WERO,
            'handlerIdentifier' => UnzerWeroPaymentHandler::class,
            'name' => 'Wero',
            'technicalName' => 'unzer_wero',
            'translations' => [
                'de-DE' => [
                    'name' => 'Wero',
                    'description' => 'Wero Zahlungen mit Unzer payments',
                ],
                'en-GB' => [
                    'name' => 'Wero',
                    'description' => 'Wero payments with Unzer payments',
                ],
            ],
        ],
    ];

    public const APPLE_PAY_DOMAIN_VERIFICATION_FILE_CONTENT = '7b2276657273696f6e223a312c227073704964223a2244303134343945313932433041444436323041333641443243393834373337433245313930423230333138343431393437433743423736364338344534323638222c22637265617465644f6e223a313731383839323737333837377d';
    private const PLUGIN_VERSION_PAYLATER_INVOICE = '5.0.0';
    private const PLUGIN_VERSION_PAYLATER_INSTALLMENT = '5.6.0';
    private const PLUGIN_VERSION_PAYLATER_DIRECT_DEBIT = '5.7.0';

    private EntityRepository $paymentMethodRepository;

    private PluginIdProvider $pluginIdProvider;

    public function __construct(EntityRepository $paymentMethodRepository, PluginIdProvider $pluginIdProvider)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->pluginIdProvider = $pluginIdProvider;
    }

    public function install(InstallContext $context, ?object $publicFileSystem): void
    {
        $this->upsertPaymentMethods($context);
        $this->createApplePayDomainVerification($publicFileSystem);
    }

    public function update(UpdateContext $context, ?object $publicFileSystem): void
    {
        $this->upsertPaymentMethods($context);
        $this->createApplePayDomainVerification($publicFileSystem);
        if ($context->getUpdatePluginVersion() === self::PLUGIN_VERSION_PAYLATER_INVOICE) {
            $this->paymentMethodRepository->upsert([
                $this->getPaymentMethod(self::PAYMENT_ID_INVOICE),
                $this->getPaymentMethod(self::PAYMENT_ID_INVOICE_SECURED),
            ], $context->getContext());

            // Make sure every Unzer payment method is linked to the plugin
            $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(UnzerPayment6::class, $context->getContext());
            $update = array_map(static function ($paymentMethod) use ($pluginId) {
                return [
                    'pluginId' => $pluginId,
                    'id' => $paymentMethod['id'],
                ];
            }, self::PAYMENT_METHODS);
            $this->paymentMethodRepository->upsert($update, $context->getContext());
        } elseif ($context->getUpdatePluginVersion() === self::PLUGIN_VERSION_PAYLATER_INSTALLMENT) {
            $this->updatePaymentMethodNamesAndTranslations($context);
        } elseif ($context->getUpdatePluginVersion() === self::PLUGIN_VERSION_PAYLATER_DIRECT_DEBIT) {
            $this->paymentMethodRepository->upsert([
                $this->getPaymentMethod(self::PAYMENT_ID_DIRECT_DEBIT_SECURED),
            ], $context->getContext());
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

    protected function updatePaymentMethodNamesAndTranslations(UpdateContext $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(UnzerPayment6::class, $context->getContext());

        $update = array_map(static function ($paymentMethod) use ($pluginId) {
            return [
                'pluginId' => $pluginId,
                'id' => $paymentMethod['id'],
                'name' => $paymentMethod['name'],
                'translations' => $paymentMethod['translations'],
            ];
        }, self::PAYMENT_METHODS);

        $this->paymentMethodRepository->upsert($update, $context->getContext());
    }

    private function upsertPaymentMethods(InstallContext $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(UnzerPayment6::class, $context->getContext());

        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if (!$this->isPaymentMethodInstalled($paymentMethod['id'], $context->getContext())) {
                // method does not exist > create with complete data
                $paymentMethod['pluginId'] = $pluginId;
                $paymentMethod['afterOrderEnabled'] = true;
                $paymentMethod['active'] = true;

                $this->paymentMethodRepository->upsert([$paymentMethod], $context->getContext());
            } else {
                // method does exist > only update necessary fields
                $upsertPayload = [
                    'id' => $paymentMethod['id'],
                    'pluginId' => $pluginId,
                    'technicalName' => $paymentMethod['technicalName'],
                ];
                $this->paymentMethodRepository->upsert([$upsertPayload], $context->getContext());
            }
        }
        $this->removeOldPaymentMethods($context);
        $this->deprecatePaymentMethods($context);
        $this->renameIdeal($context->getContext());
    }

    private function renameIdeal(Context $context): void
    {
        $criteria = new Criteria([self::PAYMENT_ID_IDEAL]);
        $criteria->addAssociation('translations.language.locale');
        /** @var PaymentMethodEntity $existingPaymentMethod */
        $existingPaymentMethod = $this->paymentMethodRepository->search($criteria, $context)->first();
        if ($existingPaymentMethod === null) {
            return;
        }
        $translations = [];
        /** @var PaymentMethodTranslationEntity $translation */
        foreach ($existingPaymentMethod->getTranslations()->getElements() as $uniqueId => $translation) {
            $newName = null;
            $newDescription = null;
            if (stripos($translation->getName(), 'wero') === false) {
                $newName = str_replace('iDEAL', 'iDEAL | Wero', $translation->getName());
            }
            if (stripos($translation->getDescription(), 'wero') === false) {
                $newDescription = str_replace('iDEAL', 'iDEAL | Wero', $translation->getDescription());
            }

            if ($newName === null && $newDescription === null) {
                continue;
            }

            $localeCode = $translation->getLanguage()->getLocale()->getCode();

            $translations[$localeCode] = [
                'languageId' => $translation->getLanguageId(),
            ];
            if ($newName !== null) {
                $translations[$localeCode]['name'] = $newName;
            }
            if ($newDescription !== null) {
                $translations[$localeCode]['description'] = $newDescription;
            }
        }

        if (empty($translations)) {
            return;
        }

        $this->paymentMethodRepository->update([[
            'id' => $existingPaymentMethod->getId(),
            'translations' => $translations,
        ]], $context);
    }

    private function deprecatePaymentMethods(InstallContext $context): void
    {
        foreach (self::DEPRECATED_PAYMENT_METHOD_IDS + self::REMOVED_PAYMENT_METHOD_IDS as $paymentMethodId) {
            try {
                $isRemoved = (\in_array($paymentMethodId, self::REMOVED_PAYMENT_METHOD_IDS, true)) ? 1 : 0;
                $this->paymentMethodRepository->upsert([[
                    'id' => $paymentMethodId,
                    'translations' => [
                        'de-DE' => [
                            'customFields' => [
                                'isDeprecated' => 1,
                                'isRemoved' => $isRemoved,
                            ],
                        ],
                        'en-GB' => [
                            'customFields' => [
                                'isDeprecated' => 1,
                                'isRemoved' => $isRemoved,
                            ],
                        ],
                    ],
                ]], $context->getContext());
            } catch (\Exception) {
                // this is not mission critical
            }
        }
    }

    private function removeOldPaymentMethods(InstallContext $context): void
    {
        foreach (self::REMOVED_PAYMENT_METHOD_IDS as $paymentMethodId) {
            /** @var PaymentMethodEntity $existingPaymentMethod */
            $existingPaymentMethod = $this->paymentMethodRepository->search(new Criteria([$paymentMethodId]), $context->getContext())->first();
            if ($existingPaymentMethod === null) {
                return;
            }

            $updatePayload = [
                'id' => $paymentMethodId,
                'active' => false,
            ];

            if (!str_contains($existingPaymentMethod->getName(), '(Veraltet)')) {
                $updatePayload['name'] = $existingPaymentMethod->getName() . ' (Veraltet)';
            }

            $this->paymentMethodRepository->update([$updatePayload], $context->getContext());
        }
    }

    private function setAllPaymentMethodsActive(bool $active, InstallContext $context): void
    {
        $upsertPayload = [];
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $paymentMethodCriteria = new Criteria([$paymentMethod['id']]);
            $hasPaymentMethod = $this->paymentMethodRepository->searchIds($paymentMethodCriteria, $context->getContext())->getTotal() > 0;

            if (!$hasPaymentMethod) {
                continue;
            }

            $upsertPayload[] = [
                'id' => $paymentMethod['id'],
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

    private function createApplePayDomainVerification(?object $publicFileSystem): void
    {
        if (!$publicFileSystem instanceof Filesystem) {
            return;
        }
        if ($publicFileSystem->has('.well-known/apple-developer-merchantid-domain-association')) {
            return;
        }
        $publicFileSystem->write(
            '.well-known/apple-developer-merchantid-domain-association',
            self::APPLE_PAY_DOMAIN_VERIFICATION_FILE_CONTENT
        );
    }
}
