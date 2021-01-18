import template from './unzer-payment-settings.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { object, types } = Shopware.Utils;

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
            isLoading: false,
            isTesting: false,
            isTestSuccessful: false,
            isSaveSuccessful: false,
            config: {},
            showWebhookModal: false
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
        }
    },

    methods: {
        getConfigValue(field) {
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
                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('sw-plugin-config.messageSaveSuccess')
                });

                this.isLoading = false;
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

        getElementBind(element) {
            const bind = object.deepCopyObject(element);

            // Add inherited values
            if (this.currentSalesChannelId !== null
                && this.inherit
                && this.actualConfigData.hasOwnProperty('null')
                && this.actualConfigData.null[bind.name] !== null) {
                if (bind.type === 'single-select' || bind.config.componentName === 'sw-entity-single-select') {
                    // Add inherited placeholder option
                    bind.placeholder = this.$tc('sw-settings.system-config.inherited');
                } else if (bind.type === 'bool') {
                    // Add inheritedValue for checkbox fields to restore the inherited state
                    bind.config.inheritedValue = this.actualConfigData.null[bind.name] || false;
                } else if (bind.type === 'password') {
                    // Add inherited placeholder and mark placeholder as password so the rendering element
                    // can choose to hide it
                    bind.placeholderIsPassword = true;
                    bind.placeholder = `${this.actualConfigData.null[bind.name]}`;
                } else if (bind.type !== 'multi-select' && !types.isUndefined(this.actualConfigData.null[bind.name])) {
                    // Add inherited placeholder
                    bind.placeholder = `${this.actualConfigData.null[bind.name]}`;
                }
            }

            // Add select properties
            if (['single-select', 'multi-select'].includes(bind.type)) {
                bind.config.labelProperty = 'name';
                bind.config.valueProperty = 'id';
            }

            return bind;
        },

        getDeliveryStatusCriteria() {
            const criteria = new Criteria(1, 100);

            criteria.addFilter(
                Criteria.equals(
                    'stateMachine.technicalName',
                    'order_delivery.state'
                )
            );

            return criteria;
        },

        openWebhookModal() {
            this.showWebhookModal = true;
        },

        closeWebhookModal() {
            this.showWebhookModal = false;
        }
    }
});
