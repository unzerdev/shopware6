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

        onSave() {
            this.$refs.systemConfig.saveAll().then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('sw-plugin-config.titleSaveSuccess'),
                    message: this.$tc('sw-plugin-config.messageSaveSuccess')
                });
            }).catch((err) => {
                this.createNotificationError({
                    title: this.$tc('sw-plugin-config.titleSaveError'),
                    message: err
                });
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
