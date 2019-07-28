import { Component } from 'src/core/shopware';
import template from './heidel-payment-detail.html.twig';
import './heidel-payment-detail.scss';

Component.register('heidel-payment-detail', {
    template,

    inject: ['HeidelPaymentService'],

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
                this.isLoading = false;

                this.$emit('reload');
            }).catch((errorResponse) => {
                console.log(errorResponse);
            });
        },
    }
});
