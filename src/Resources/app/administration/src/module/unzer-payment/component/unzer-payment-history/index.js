import template from './unzer-payment-history.html.twig';

const { Component, Module, Mixin } = Shopware;

Component.register('unzer-payment-history', {
    template,

    inject: ['repositoryFactory', 'UnzerPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            showCancelModal: false,
            isCancelLoading: false,
            cancelAmount: 0,
        };
    },

    props: {
        paymentResource: {
            type: Object,
            required: true
        }
    },

    computed: {
        unzerMaxDigits() {
            const unzerPaymentModule = Module.getModuleRegistry().get('unzer-payment');

            if(!unzerPaymentModule || !unzerPaymentModule.manifest) {
                return 4;
            }

            return unzerPaymentModule.manifest.maxDigits;
        },

        orderTransactionRepository: function () {
            return this.repositoryFactory.create('order_transaction');
        },

        decimalPrecision() {
            if(!this.paymentResource || !this.paymentResource.amount || !this.paymentResource.amount.decimalPrecision) {
                return this.unzerMaxDigits;
            }

          return Math.min(this.unzerMaxDigits, this.paymentResource.amount.decimalPrecision)
        },

        data: function () {
            const data = [];

            Object.values(this.paymentResource.transactions).forEach((transaction) => {
                const amount = this.$options.filters.currency(
                    this.formatAmount(parseFloat(transaction.amount), this.decimalPrecision),
                    this.paymentResource.currency
                );

                const date = this.$options.filters.date(
                    transaction.date,
                    {
                        hour: 'numeric',
                        minute: 'numeric',
                        second: 'numeric'
                    }
                );

                data.push({
                    type: this.transactionTypeRenderer(transaction.type),
                    amount: amount,
                    date: date,
                    resource: transaction
                });
            });

            return data;
        },

        columns: function () {
            return [
                {
                    property: 'type',
                    label: this.$tc('unzer-payment.paymentDetails.history.column.type'),
                    rawData: true
                },
                {
                    property: 'amount',
                    label: this.$tc('unzer-payment.paymentDetails.history.column.amount'),
                    rawData: true
                },
                {
                    property: 'date',
                    label: this.$tc('unzer-payment.paymentDetails.history.column.date'),
                    rawData: true
                }
            ];
        }
    },

    methods: {
        transactionTypeRenderer: function (value) {
            switch (value) {
                case 'authorization':
                    return this.$tc('unzer-payment.paymentDetails.history.type.authorization');
                case 'charge':
                    return this.$tc('unzer-payment.paymentDetails.history.type.charge');
                case 'shipment':
                    return this.$tc('unzer-payment.paymentDetails.history.type.shipment');
                case 'refund':
                    return this.$tc('unzer-payment.paymentDetails.history.type.refund');
                case 'cancellation':
                    return this.$tc('unzer-payment.paymentDetails.history.type.cancellation');
                default:
                    return this.$tc('unzer-payment.paymentDetails.history.type.default');
            }
        },

        reload: function () {
            this.$emit('reload');
            this.$emit('reloadOrderDetails');
        },

        formatAmount(cents, decimalPrecision) {
            return cents / (10 ** decimalPrecision);
        },

        openCancelModal(item, cancelAmount) {
            this.showCancelModal = item.resource.id;
            this.cancelAmount = cancelAmount;
        },

        closeCancelModal() {
            this.showCancelModal = false;
            this.cancelAmount = 0;
        },

        cancel() {
            this.isCancelLoading = true;

            this.UnzerPaymentService.cancelTransaction(
                this.paymentResource.orderId,
                this.paymentResource.id,
                this.cancelAmount
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.cancelSuccessTitle'),
                    message: this.$tc('unzer-payment.paymentDetails.notifications.cancelSuccessMessage')
                });

                this.reload();
            }).catch((errorResponse) => {
                let message = errorResponse.response.data.errors[0];

                if (message === 'generic-error') {
                    message = this.$tc('unzer-payment.paymentDetails.notifications.cancelErrorMessage');
                }

                this.createNotificationError({
                    title: this.$tc('unzer-payment.paymentDetails.notifications.cancelErrorTitle'),
                    message: message
                });

                this.isCancelLoading = false;
            });
        }
    }
});
