import template from './unzer-payment-settings.html.twig';
import './unzer-payment-settings.scss';

const { Component, Mixin, Context } = Shopware;

Component.register('unzer-payment-settings', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: [
        'repositoryFactory',
        'UnzerPaymentConfigurationService'
    ],

    data() {
        return {
            isLoading: true,
            isLoadingWebhooks: true,
            selectedKeyPairForTesting: false,
            isTestSuccessful: false,
            isSaveSuccessful: false,
            config: {},
            webhooks: [],
            loadedWebhooksPrivateKey: false,
            selectedSalesChannelId: null,
            keyPairSettings: [
                {
                    key: 'b2b-eur',
                    group: 'paylaterInvoice',
                },
                {
                    key: 'b2b-chf',
                    group: 'paylaterInvoice',
                },
                {
                    key: 'b2c-eur',
                    group: 'paylaterInvoice',
                },
                {
                    key: 'b2c-chf',
                    group: 'paylaterInvoice',
                },
                {
                    key: 'b2c-eur',
                    group: 'paylaterInstallment',
                },
                {
                    key: 'b2c-chf',
                    group: 'paylaterInstallment',
                }
            ],
            openModalKeyPair: null,
        };
    },

    metaInfo() {
        return {
            title: 'UnzerPayment'
        };
    },

    computed: {
        paymentMethodRepository() {
            return this.repositoryFactory.create('payment_method');
        },

        arrowIconName() {
            const match = Context.app.config.version.match(/((\d+)\.?(\d+?)\.?(\d+)?\.?(\d*))-?([A-z]+?\d+)?/i);

            if (match[3] >= 5) {
                return 'regular-chevron-right-xs';
            }

            return 'small-arrow-medium-right';
        },

        defaultKeyPair() {
            return {
                privateKey: this.getConfigValue('privateKey'),
                publicKey: this.getConfigValue('publicKey'),
            }
        }
    },

    watch: {
        openModalKeyPair(val) {
            if (val && val.privateKey !== this.loadedWebhooksPrivateKey) {
                this.loadWebhooks(val.privateKey);
            }
        },
    },

    methods: {
        getConfigValue(field) {
            if (!this.config || !this.$refs.systemConfig  || !this.$refs.systemConfig.actualConfigData || !this.$refs.systemConfig.actualConfigData.null) {
                return '';
            }

            const defaultConfig = this.$refs.systemConfig.actualConfigData.null;

            return this.config[`UnzerPayment6.settings.${field}`]
                || defaultConfig[`UnzerPayment6.settings.${field}`];
        },

        onValidateCredentials(keyPairSetting) {
            this.isTestSuccessful = false;
            this.selectedKeyPairForTesting = keyPairSetting;

            const credentials = {
                publicKey: keyPairSetting.publicKey,
                privateKey: keyPairSetting.privateKey,
                salesChannel: this.$refs.systemConfig.currentSalesChannelId
            };

            this.UnzerPaymentConfigurationService.validateCredentials(credentials).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('unzer-payment-settings.form.message.success.title'),
                    message: this.$tc('unzer-payment-settings.form.message.success.message')
                });

                this.isTestSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('unzer-payment-settings.form.message.error.title'),
                    message: this.$tc('unzer-payment-settings.form.message.error.message')
                });

                this.onTestFinished();
            });
        },

        onTestFinished() {
            this.selectedKeyPairForTesting = false;
            this.isTestSuccessful = false;
        },

        onSave() {
            this.isLoading = true;

            ['paylaterInvoice', 'paylaterInstallment'].forEach((group) => {
                this.config[`UnzerPayment6.settings.${group}`] = [];
            });

            this.keyPairSettings.reduce((config, keyPairSetting) => {
                if (!keyPairSetting || !keyPairSetting.privateKey || !keyPairSetting.publicKey) {
                    return config;
                }

                config[`UnzerPayment6.settings.${keyPairSetting.group}`].push(keyPairSetting);

                return config;
            }, this.config);

            this.$refs.systemConfig.saveAll().then(() => {
                this.isSaveSuccessful = true;

                let messageSaveSuccess = this.$tc('sw-plugin-config.messageSaveSuccess');

                if (messageSaveSuccess === 'sw-plugin-config.messageSaveSuccess') {
                    messageSaveSuccess = this.$tc('sw-extension-store.component.sw-extension-config.messageSaveSuccess');
                }

                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: messageSaveSuccess
                });

                this.$refs.applePayCertificates.onSave().then(() => {
                    this.isLoading = false;
                });
            }).catch((err) => {
                this.isSaveSuccessful = false;

                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: err
                });

                this.isLoading = false;
            });
        },

        onConfigChange(config) {
            this.config = config;
            this.isLoading = false;
            this.syncKeyPairConfig();
            this.$refs.applePayCertificates.loadData();
        },

        onLoadingChanged(value) {
            this.isLoading = value;
        },

        onSalesChannelChanged(config, salesChannelId) {
            if (config) {
                this.onConfigChange(config);
            }

            this.selectedSalesChannelId = salesChannelId;
        },

        onWebhookRegistered(privateKey) {
            this.loadWebhooks(privateKey);
        },

        loadWebhooks(privateKey) {
            this.isLoadingWebhooks = true;

            this.UnzerPaymentConfigurationService.getWebhooks(privateKey)
                .then((response) => {
                    this.webhooks = response;
                    this.webhookSelection = null;
                    this.webhookSelectionLength = 0;
                    this.loadedWebhooksPrivateKey = privateKey;
                })
                .catch(() => {
                    this.webhooks = [];
                    this.loadedWebhooksPrivateKey = false;
                })
                .finally(() => {
                    this.isLoadingWebhooks = false;
                    this.isClearingSuccessful = false;
                });
        },

        getBind(element, config) {
            let originalElement;

            if (config !== this.config) {
                this.config = config;
            }

            this.$refs.systemConfig.config.forEach((configElement) => {
                configElement.elements.forEach((child) => {
                    if (child.name === element.name) {
                        originalElement = child;
                        return;
                    }
                });
            });

            return originalElement || element;
        },

        keyPairSettingTitle(keyPairSetting) {
            return this.$tc(`unzer-payment.methods.${keyPairSetting.group}.${keyPairSetting.key}`);
        },

        isShowWebhooksButtonEnabled(keyPairSetting) {
            return keyPairSetting && keyPairSetting.privateKey && keyPairSetting.publicKey;
        },

        isRegisterWebhooksButtonEnabled(keyPairSetting) {
            return !this.isLoading && keyPairSetting && keyPairSetting.privateKey;
        },

        syncKeyPairConfig() {
            const me = this;
            ['paylaterInvoice', 'paylaterInstallment'].forEach((group) => {
                if (!this.config[`UnzerPayment6.settings.${group}`]) {
                    return;
                }
                this.config[`UnzerPayment6.settings.${group}`].forEach((configKeyPairSetting) => {
                    me.keyPairSettings.forEach((keyPairSetting, index, collection) => {
                        if (keyPairSetting.group === configKeyPairSetting.group && keyPairSetting.key === configKeyPairSetting.key) {
                            collection[index] = configKeyPairSetting;
                        }
                    });
                });
            });
        }
    }
});
