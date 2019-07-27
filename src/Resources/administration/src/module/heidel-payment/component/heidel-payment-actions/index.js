import { Component } from 'src/core/shopware';
import template from './heidel-payment-actions.html.twig';

Component.register('heidel-payment-actions', {
    template,

    inject: ['HeidelPaymentService'],

    data() {
        return {
            isLoading: false,
            transactionAmount: this.paymentResource.basket.amountTotal
        };
    },

    props: {
        paymentResource: {
            type: Object,
            required: true
        },
    },

    methods: {
        isCapturePossible() {
            return true;
        },

        isRefundPossible() {
            return true;
        },

        isShipPossible() {
            return true;
        },

        charge() {
            this.isLoading = true;

            this.HeidelPaymentService.chargeTransaction(
                this.paymentResource.orderId,
                this.transactionAmount
            ).then(() => {
                this.isLoading = false;

                this.$emit('reload');
            }).catch((errorResponse) => {
                console.log(errorResponse);
            });
        },

        refund() {
            this.isLoading = true;

            this.HeidelPaymentService.refundTransaction(
                this.paymentResource.orderId,
                this.transactionAmount
            ).then(() => {
                this.$emit('reload');
            }).catch((errorResponse) => {
                console.log(errorResponse);
            });
        },

        ship() {
            this.isLoading = true;
            this.$emit('reload');
        }
    }
});
