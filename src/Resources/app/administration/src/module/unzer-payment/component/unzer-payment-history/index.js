import template from './unzer-payment-history.html.twig';

const { Component, Module } = Shopware;

Component.register('unzer-payment-history', {
    template,

    inject: ['repositoryFactory'],

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
                case 'cancellation':
                    return this.$tc('unzer-payment.paymentDetails.history.type.cancellation');
                default:
                    return this.$tc('unzer-payment.paymentDetails.history.type.default');
            }
        },

        reloadPaymentDetails: function () {
            this.$emit('reload');
        },

        formatAmount(cents, decimalPrecision) {
            return cents / (10 ** decimalPrecision);
        }
    }
});
