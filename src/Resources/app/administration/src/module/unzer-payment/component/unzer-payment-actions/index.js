import template from './unzer-payment-actions.html.twig';
import './unzer-payment-actions.scss';

const { Component, Mixin } = Shopware;
const reasonCodes = {
    CANCEL: 'CANCEL',
    RETURN: 'RETURN',
    CREDIT: 'CREDIT',
}

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
            transactionAmount: 0.00,
            reasonCode: null
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
            let amount = 0;

            if (this.isRefundPossible) {
                amount = this.transactionResource.amount;
            }

            if (this.isChargePossible) {
                amount = this.paymentResource.amount.remaining;
            }

            if ('remainingAmount' in this.transactionResource) {
                amount = this.transactionResource.remainingAmount;
            }

            return amount / (10 ** this.paymentResource.amount.decimalPrecision);
        },


        reasonCodeSelection() {
            return [
                {
                    label: this.$tc('unzer-payment.paymentDetails.actions.reason.cancel'),
                    value: reasonCodes.CANCEL,
                },
                {
                    label: this.$tc('unzer-payment.paymentDetails.actions.reason.credit'),
                    value: reasonCodes.CREDIT,
                },
                {
                    label: this.$tc('unzer-payment.paymentDetails.actions.reason.return'),
                    value: reasonCodes.RETURN,
                }
            ];
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
                let message = errorResponse.response.data.errors[0];

                if (message === 'generic-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.genericErrorMessage');
                }

                if (message === 'paylater-invoice-document-required') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.paylaterInvoiceDocumentRequiredErrorMessage');
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
                this.transactionAmount,
                this.reasonCode
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.refundSuccessTitle'),
                    message: this.$tc('unzer-payment.paymentDetails.notifications.refundSuccessMessage')
                });

                this.isSuccessful = true;

                this.$emit('reload');
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.errors[0];

                if (message === 'generic-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.genericErrorMessage');
                }

                this.createNotificationError({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.refundErrorTitle'),
                    message: message
                });

                this.isLoading = false;
            });
        },

        startCancel() {
            this.$emit('cancel', this.transactionAmount);
        },
    }
});
