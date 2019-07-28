import { Component, Mixin } from 'src/core/shopware';
import template from './heidel-payment-detail.html.twig';
import './heidel-payment-detail.scss';

Component.register('heidel-payment-detail', {
    template,

    inject: ['HeidelPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
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

                this.$emit('reload');
            }).catch((errorResponse) => {
                this.createNotificationError({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.shipErrorTitle'),
                    message: errorResponse.response.data.message
                });

                this.isLoading = false;
            });
        },
    }
});
