import template from './unzer-payment-actions.html.twig';
import './unzer-payment-actions.scss';

const { Component, Mixin } = Shopware;

Component.register('unzer-payment-actions', {
    template,

    inject: ['UnzerPaymentService'],

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
        },

        decimalPrecision: {
            type: Number,
            required: true,
            default: 4
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
            if (this.isRefundPossible) {
                return this.transactionResource.amount;
            }

            if (this.isChargePossible) {
                return this.paymentResource.amount.remaining;
            }

            return 0;
        }
    },

    created() {
        this.transactionAmount = this.maxTransactionAmount;
    },

    methods: {
        charge() {
            this.isLoading = true;

            this.UnzerPaymentService.chargeTransaction(
                this.paymentResource.orderId,
                this.transactionResource.id,
                this.transactionAmount
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.chargeSuccessTitle'),
                    message: this.$tc('unzer-payment.paymentDetails.notifications.chargeSuccessMessage')
                });

                this.isSuccessful = true;

                this.$emit('reload');
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.message;

                if (message === 'generic-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.genericErrorMessage');
                }

                this.createNotificationError({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.chargeErrorTitle'),
                    message: message
                });

                this.isLoading = false;
            });
        },

        refund() {
            this.isLoading = true;

            this.UnzerPaymentService.refundTransaction(
                this.paymentResource.orderId,
                this.transactionResource.id,
                this.transactionAmount
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.refundSuccessTitle'),
                    message: this.$tc('unzer-payment.paymentDetails.notifications.refundSuccessMessage')
                });

                this.isSuccessful = true;

                this.$emit('reload');
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.message;

                if (message === 'generic-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.genericErrorMessage');
                }

                this.createNotificationError({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.refundErrorTitle'),
                    message: message
                });

                this.isLoading = false;
            });
        }
    }
});
