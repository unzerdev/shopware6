const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './heidel-payment-settings.html.twig';

Component.register('heidel-payment-settings', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet'),
    ],

    inject: [
        'repositoryFactory',
        'HeidelPaymentConfigurationService'
    ],

    data() {
        return {
            isLoading: false,
            isTesting: false,
            isTestSuccessful: false,
            isSaveSuccessful: false,
            config: {},
        };
    },

    metaInfo() {
        return {
            title: 'Heidelpay'
        };
    },

    computed: {
        paymentMethodRepository() {
            return this.repositoryFactory.create('payment_method');
        },
    },

    methods: {
        getConfigValue(field) {
            const defaultConfig = this.$refs.systemConfig.actualConfigData.null;

            return this.config[`HeidelPayment6.settings.${field}`]
                || defaultConfig[`HeidelPayment6.settings.${field}`];
        },

        onValidateCredentials() {
            this.isTestSuccessful = false;
            this.isTesting = true;

            const credentials = {
                'publicKey': this.getConfigValue('publicKey'),
                'privateKey': this.getConfigValue('privateKey'),
                'salesChannel': this.$refs.systemConfig.currentSalesChannelId,
            };

            this.HeidelPaymentConfigurationService.validateCredentials(credentials).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('heidel-payment-settings.form.message.success.title'),
                    message:  this.$tc('heidel-payment-settings.form.message.success.message'),
                });

                this.isTestSuccessful = true;
                this.isTesting = false;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('heidel-payment-settings.form.message.error.title'),
                    message:  this.$tc('heidel-payment-settings.form.message.error.message'),
                });
                this.isTesting = false;
            });
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
    },
});
