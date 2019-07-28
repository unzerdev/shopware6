import { Component } from 'src/core/shopware';
import template from './heidel-payment-actions.html.twig';
import './heidel-payment-actions.scss';

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
        transactionResource: {
            type: Object,
            required: true
        },

        paymentResource: {
            type: Object,
            required: true
        },
    },

    computed: {
        isChargePossible: function () {
            return true;
        },

        isRefundPossible: function () {
            return false;
        }
    },

    methods: {
        charge() {
            this.$emit('reload');
        },

        refund() {
            this.isLoading = true;

            this.HeidelPaymentService.refundTransaction(
                this.paymentResource.orderId,
                this.transactionAmount
            ).then(() => {
                this.isLoading = true;
                this.$emit('reload');
            }).catch((errorResponse) => {
                console.log(errorResponse);
            });
        },
    }
});
