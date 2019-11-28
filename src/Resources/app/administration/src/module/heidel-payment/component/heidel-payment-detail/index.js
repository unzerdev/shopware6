const { Component, Mixin } = Shopware;

import template from './heidel-payment-detail.html.twig';

Component.register('heidel-payment-detail', {
    template,

    inject: ['HeidelPaymentService'],

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
        },
    },

    methods: {
        ship() {
            this.isLoading = true;

            this.HeidelPaymentService.ship(
                this.paymentResource.orderId
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.shipSuccessTitle'),
                    message: this.$tc('heidel-payment.paymentDetails.notifications.shipSuccessMessage')
                });

                this.isSuccessful = true;

                this.$emit('reload');
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.message;

                if (message === 'generic-error') {
                    message = this.$tc('heidel-payment.paymentDetails.notifications.genericErrorMessage');
                } else if (message === 'invoice-missing-error') {
                    message = this.$tc('heidel-payment.paymentDetails.notifications.invoiceNotFoundMessage');
                }

                this.createNotificationError({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.shipErrorTitle'),
                    message: message
                });

                this.isLoading = false;
            });
        },
    }
});
