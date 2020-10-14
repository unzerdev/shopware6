import template from './heidel-payment-actions.html.twig';

const { Component, Mixin } = Shopware;

Component.register('heidel-payment-actions', {
    template,

    inject: ['HeidelPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSuccessful: false,
            transactionAmount: 0.00
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
        }
    },

    computed: {
        isChargePossible: function () {
            return this.transactionResource.type === 'authorization';
        },

        isRefundPossible: function () {
            return this.transactionResource.type === 'charge';
        },

        maxTransactionAmount() {
            let transactionVal = 0;
            if (this.isRefundPossible) {
                transactionVal = this.paymentResource.amount.charged - this.paymentResource.amount.canceled;
            } else if (this.isChargePossible) {
                transactionVal = this.paymentResource.amount.remaining;
            }

            return transactionVal;
        }
    },

    created() {
        this.transactionAmount = this.maxTransactionAmount;
    },

    methods: {
        charge() {
            this.isLoading = true;

            this.HeidelPaymentService.chargeTransaction(
                this.paymentResource.orderId,
                this.transactionResource.id,
                this.transactionAmount
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.chargeSuccessTitle'),
                    message: this.$tc('heidel-payment.paymentDetails.notifications.chargeSuccessMessage')
                });

                this.isSuccessful = true;

                this.$emit('reload');
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.message;

                if (message === 'generic-error') {
                    message = this.$tc('heidel-payment.paymentDetails.notifications.genericErrorMessage');
                }

                this.createNotificationError({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.chargeErrorTitle'),
                    message: message
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

                this.isSuccessful = true;

                this.$emit('reload');
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.message;

                if (message === 'generic-error') {
                    message = this.$tc('heidel-payment.paymentDetails.notifications.genericErrorMessage');
                }

                this.createNotificationError({
                    title: this.$tc('heidel-payment.paymentDetails.notifications.refundErrorTitle'),
                    message: message
                });

                this.isLoading = false;
            });
        }
    }
});
