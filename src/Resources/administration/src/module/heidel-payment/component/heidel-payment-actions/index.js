import { Component, Mixin } from 'src/core/shopware';
import template from './heidel-payment-actions.html.twig';
import './heidel-payment-actions.scss';

Component.register('heidel-payment-actions', {
    template,

    inject: ['HeidelPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

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
            return this.transactionResource.type === 'authorization';
        },

        isRefundPossible: function () {
            return this.transactionResource.type === 'charge';
        }
    },

    methods: {
        charge() {
            this.isLoading = true;

            this.HeidelPaymentService.chargeTransaction(
                this.paymentResource.orderId,
                this.transactionAmount
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.chargeSuccessTitle'),
                    message: this.$tc('heidel-payment.paymentDetails.notifications.chargeSuccessMessage')
                });

                this.$emit('reload');
            }).catch((errorResponse) => {
                this.createNotificationError({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.chargeErrorTitle'),
                    message: errorResponse.response.data.message
                });

                this.isLoading = false;
            });
        },

        refund() {
            this.isLoading = true;

            this.HeidelPaymentService.refundTransaction(
                this.paymentResource.orderId,
                this.transactionResource.id,
                this.transactionAmount
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.refundSuccessTitle'),
                    message: this.$tc('heidel-payment.paymentDetails.notifications.refundSuccessMessage')
                });

                this.$emit('reload');
            }).catch((errorResponse) => {
                this.createNotificationError({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.refundErrorTitle'),
                    message: errorResponse.response.data.message
                });

                this.isLoading = false;
            });
        },
    }
});
