import template from './unzer-apple-pay-certificates.html.twig';

Shopware.Component.register('unzer-payment-apple-pay-certificates', {
    template,

    mixins: [
        Shopware.Mixin.getByName('notification')
    ],

    inject: [
        'repositoryFactory',
        'UnzerPaymentApplePayService'
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: false
        },
        selectedSalesChannelId: {
            type: String,
            required: false
        }
    },

    data() {
        return {
            isUpdating: false,
            isUpdateSuccessful: false,
            isDataLoading: false,
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadData();
        },

        loadData() {
            let me = this;

            me.isDataLoading = true;

            this.UnzerPaymentApplePayService.checkCertificates({
                salesChannelId: this.selectedSalesChannelId
            })
                .then((response) => {
                    me.isUpdateSuccessful = true;

                    if (undefined !== response) {
                        me.messageGeneration(response);
                    }

                    this.$emit('certificate-updated', response);
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('unzer-payment-settings.webhook.globalError.title'),
                        message: this.$tc('unzer-payment-settings.webhook.globalError.message')
                    });
                })
                .finally(() => {
                    me.isDataLoading = false;
                });
        },

        updateCertificates() {
            const me = this;
            this.isUpdateSuccessful = false;
            this.isUpdating = true;

            this.UnzerPaymentApplePayService.updateCertificates({
                // TODO
            })
                .then((response) => {
                    me.isUpdateSuccessful = true;

                    this.createNotificationSuccess({
                        title: this.$tc('unzer-payment-settings.apple-pay.update.success.title'),
                        message: this.$tc('unzer-payment-settings.apple-pay.update.success.message')
                    });

                    this.$emit('certificate-updated', response);
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('unzer-payment-settings.webhook.globalError.title'),
                        message: this.$tc('unzer-payment-settings.webhook.globalError.message')
                    });
                })
                .finally(() => {
                    me.isUpdating = false;
                });
        },
    }
});
