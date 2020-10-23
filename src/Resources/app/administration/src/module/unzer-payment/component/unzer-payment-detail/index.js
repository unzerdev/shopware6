import template from './unzer-payment-detail.html.twig';

const { Component, Mixin } = Shopware;

Component.register('unzer-payment-detail', {
    template,

    inject: ['UnzerPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSuccessful: false
        };
    },

    props: {
        paymentResource: {
            type: Object,
            required: true
        }
    },

    methods: {
        ship() {
            this.isLoading = true;

            this.UnzerPaymentService.ship(
                this.paymentResource.orderId
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.shipSuccessTitle'),
                    message: this.$tc('unzer-payment.paymentDetails.notifications.shipSuccessMessage')
                });

                this.isSuccessful = true;

                this.$emit('reload');
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.message;

                if (message === 'generic-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.genericErrorMessage');
                } else if (message === 'invoice-missing-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.invoiceNotFoundMessage');
                }

                this.createNotificationError({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.shipErrorTitle'),
                    message: message
                });

                this.isLoading = false;
            });
        }
    }
});
