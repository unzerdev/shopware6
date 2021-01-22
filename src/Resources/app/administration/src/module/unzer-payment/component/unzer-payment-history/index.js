import template from './unzer-payment-history.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

const UNZER_MAX_DIGITS = 4;

Component.register('unzer-payment-history', {
    template,

    inject: ['repositoryFactory'],

    props: {
        paymentResource: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            decimalPrecision: UNZER_MAX_DIGITS
        };
    },

    computed: {
        orderTransactionRepository: function () {
            return this.repositoryFactory.create('order_transaction');
        },

        data: function () {
            const data = [];

            Object.values(this.paymentResource.transactions).forEach((transaction) => {
                const amount = this.$options.filters.currency(
                    parseFloat(transaction.amount),
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

    created() {
        const orderTransactionCriteria = new Criteria();
        orderTransactionCriteria.addAssociation('order.currency');

        this.orderTransactionRepository.get(this.paymentResource.orderId, Shopware.Context.api, orderTransactionCriteria)
            .then((result) => {
                if (result && result.order && result.order.currency) {
                    this.decimalPrecision = Math.min(UNZER_MAX_DIGITS, result.order.currency.decimalPrecision)
                }
            });
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
        }
    }
});
