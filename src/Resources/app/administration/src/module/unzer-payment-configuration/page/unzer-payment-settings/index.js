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
            webhookSelection: null,
            webhookSelectionLength: 0,
            isClearing: false,
            isClearingSuccessful: false,
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

        webhookColumns() {
            return [
                {
                    property: 'event',
                    dataIndex: 'event',
                    label: 'Event'
                },
                {
                    property: 'url',
                    dataIndex: 'url',
                    label: 'URL'
                }
            ];
        },

        arrowIconName() {
            const match = Context.app.config.version.match(/((\d+)\.?(\d+?)\.?(\d+)?\.?(\d*))-?([A-z]+?\d+)?/i);

            if (match[3] >= 5) {
                return 'regular-chevron-right-xs';
            }

            return 'small-arrow-medium-right';
        },
    },

    watch: {
        openModalKeyPair(val) {
            this.openModalKeyPair = val;

            if (!val) {
                this.webhooks = [];
                this.webhookSelection = null;
                this.webhookSelectionLength= 0;
            }

            let keyPairSetting = this.keyPairSettings.find(function(element) {
                return element.key === val.key && element.group === val.group;
            });

            this.loadWebhooks(keyPairSetting?.privateKey);
        }
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
                if (!keyPairSetting?.privateKey || !keyPairSetting?.publicKey) {
                    return config;
                }

                config[`UnzerPayment6.settings.${keyPairSetting.group}`].push(keyPairSetting);

                return config;
            }, this.config);

            this.$refs.systemConfig.saveAll().then(() => {
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
                })
                .finally(() => {
                    this.isLoadingWebhooks = false;
                    this.isClearingSuccessful = false;
                });
        },

        onSelectWebhook(selectedItems) {
            this.webhookSelectionLength = Object.keys(selectedItems).length;
            this.webhookSelection = selectedItems;
        },

        clearWebhooks(privateKey) {
            const me = this;
            this.isClearingSuccessful = false;
            this.isClearing = true;
            this.isLoading = true;

            this.UnzerPaymentConfigurationService.clearWebhooks({
                privateKey: privateKey,
                selection: this.webhookSelection
            })
                .then((response) => {
                    me.isClearingSuccessful = true;
                    me.isLoadingWebhooks = true;
                    me.webhookSelection = [];
                    me.webhookSelectionLength = 0;

                    me.$refs.webhookDataGrid.resetSelection();

                    this.loadWebhooks(privateKey);
                    if (undefined !== response) {
                        me.messageGeneration(response);
                    }
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('unzer-payment-settings.webhook.globalError.title'),
                        message: this.$tc('unzer-payment-settings.webhook.globalError.message')
                    });
                })
                .finally(() => {
                    me.isLoading = false;
                    me.isClearing = false;
                });
        },

        messageGeneration(data) {
            const domainAmount = data.length;

            Object.keys(data).forEach((url) => {
                if (data[url].success) {
                    this.createNotificationSuccess({
                        title: this.$tc(data[url].message, domainAmount),
                        message: this.$tc('unzer-payment-settings.webhook.messagePrefix', domainAmount) + url
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc(data[url].message, domainAmount),
                        message: this.$tc('unzer-payment-settings.webhook.messagePrefix', domainAmount) + url
                    });
                }
            });
        },

        onClearingFinished() {
            this.isClearingSuccessful = false;
            this.isClearing = false;
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

        onModalClose() {
            this.openModalKeyPair = false;
        },

        keyPairSettingTitle(keyPairSetting) {
            return this.$tc(`unzer-payment.methods.${keyPairSetting.group}.${keyPairSetting.key}`);
        },

        isShowWebhooksButtonEnabled(keyPairSetting) {
            return keyPairSetting?.privateKey && keyPairSetting?.publicKey;
        },

        isRegisterWebhooksButtonEnabled(keyPairSetting) {
            return !this.isLoading && keyPairSetting?.privateKey;
        },

        syncKeyPairConfig() {
            const me = this;
            ['paylaterInvoice', 'paylaterInstallment'].forEach((group) => {
                this.config[`UnzerPayment6.settings.${group}`]?.forEach((configKeyPairSetting) => {
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
