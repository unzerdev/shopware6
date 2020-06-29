import template from './heidel-payment-history.html.twig';

const { Component } = Shopware;

Component.register('heidel-payment-history', {
    template,

    props: {
        paymentResource: {
            type: Object,
            required: true
        }
    },

    computed: {
        data: function () {
            const data = [];

            this.paymentResource.transactions.forEach((transaction) => {
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
                    label: this.$tc('heidel-payment.paymentDetails.history.column.type'),
                    rawData: true
                },
                {
                    property: 'amount',
                    label: this.$tc('heidel-payment.paymentDetails.history.column.amount'),
                    rawData: true
                },
                {
                    property: 'date',
                    label: this.$tc('heidel-payment.paymentDetails.history.column.date'),
                    rawData: true
                }
            ];
        }
    },

    methods: {
        transactionTypeRenderer: function (value) {
            switch (value) {
                case 'authorization':
                    return this.$tc('heidel-payment.paymentDetails.history.type.authorization');
                case 'charge':
                    return this.$tc('heidel-payment.paymentDetails.history.type.charge');
                case 'shipment':
                    return this.$tc('heidel-payment.paymentDetails.history.type.shipment');
                case 'cancellation':
                    return this.$tc('heidel-payment.paymentDetails.history.type.cancellation');
                default:
                    return this.$tc('heidel-payment.paymentDetails.history.type.default');
            }
        },

        reloadPaymentDetails: function () {
            this.$emit('reload');
        }
    }
});
