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
            isTesting: false,
            isTestSuccessful: false,
            isSaveSuccessful: false,
            config: {},
            showWebhookModal: false,
            webhooks: [],
            webhookSelection: null,
            webhookSelectionLength: 0,
            isClearing: false,
            isClearingSuccessful: false,
            selectedSalesChannelId: null
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

        onValidateCredentials() {
            this.isTestSuccessful = false;
            this.isTesting = true;

            const credentials = {
                publicKey: this.getConfigValue('publicKey'),
                privateKey: this.getConfigValue('privateKey'),
                salesChannel: this.$refs.systemConfig.currentSalesChannelId
            };

            this.UnzerPaymentConfigurationService.validateCredentials(credentials).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('unzer-payment-settings.form.message.success.title'),
                    message: this.$tc('unzer-payment-settings.form.message.success.message')
                });

                this.isTestSuccessful = true;
                this.isTesting = false;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('unzer-payment-settings.form.message.error.title'),
                    message: this.$tc('unzer-payment-settings.form.message.error.message')
                });
                this.isTesting = false;
            });
        },

        onTestFinished() {
            this.isTestSuccessful = false;
        },

        onSave() {
            this.isLoading = true;

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
            this.loadWebhooks();
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

        onWebhookRegistered() {
            this.loadWebhooks();
        },

        loadWebhooks() {
            this.isLoadingWebhooks = true;

            this.UnzerPaymentConfigurationService.getWebhooks(this.getConfigValue('privateKey'))
                .then((response) => {
                    console.log(response);
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

        clearWebhooks() {
            const me = this;
            this.isClearingSuccessful = false;
            this.isClearing = true;
            this.isLoading = true;

            this.UnzerPaymentConfigurationService.clearWebhooks({
                privateKey: this.getConfigValue('privateKey'),
                selection: this.webhookSelection
            })
                .then((response) => {
                    me.isClearingSuccessful = true;
                    me.isLoadingWebhooks = true;
                    me.webhookSelection = [];
                    me.webhookSelectionLength = 0;

                    me.$refs.webhookDataGrid.resetSelection();

                    this.loadWebhooks();
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

        openWebhookModal() {
            this.showWebhookModal = true;
        },

        closeWebhookModal() {
            this.showWebhookModal = false;
        }
    }
});
