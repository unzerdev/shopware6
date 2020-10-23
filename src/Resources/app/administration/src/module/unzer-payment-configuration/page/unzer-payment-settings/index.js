import template from './unzer-payment-settings.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

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
                    title: this.$tc('sw-plugin-config.titleSaveSuccess'),
                    message: this.$tc('sw-plugin-config.messageSaveSuccess')
                });

                this.isLoading = false;
            }).catch((err) => {
                this.createNotificationError({
                    title: this.$tc('sw-plugin-config.titleSaveError'),
                    message: err
                });

                this.isLoading = false;
            });
        },

        onConfigChange(config) {
            this.config = config;
        },

        getBind(element, config) {
            if (config !== this.config) {
                this.config = config;
            }

            return element;
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
