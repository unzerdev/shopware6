import { Component } from 'src/core/shopware';
import template from './heidel-payment-history.html.twig';

Component.register('heidel-payment-history', {
    template,

    props: {
        paymentResource: {
            type: Object,
            required: true
        },
    },

    computed: {
        data: function () {
            let data = [];

            this.paymentResource.transactions.forEach((transaction) => {
                data.push({
                    type: this.transactionTypeRenderer(transaction.type),
                    amount: transaction.amount,
                    date: transaction.date
                })
            });

            return data;
        },
        columns: function () {
            return [
                {
                    property: 'type',
                    label: this.$tc('heidel-payment.transactionHistory.column.type'),
                    rawData: true
                },
                {
                    property: 'amount',
                    label: this.$tc('heidel-payment.transactionHistory.column.amount'),
                    rawData: true
                },
                {
                    property: 'date',
                    label: this.$tc('heidel-payment.transactionHistory.column.date'),
                    rawData: true
                },
            ];
        }
    },

    methods: {
        transactionTypeRenderer: function (value) {
            switch (value) {
                case 'authorization':
                    return this.$tc('heidel-payment.transactionHistory.type.authorization');
                case 'charge':
                    return this.$tc('heidel-payment.transactionHistory.type.charge');
                case 'shipment':
                    return this.$tc('heidel-payment.transactionHistory.type.shipment');
                case 'cancellation':
                    return this.$tc('heidel-payment.transactionHistory.type.cancellation');
                default:
                    return this.$tc('heidel-payment.transactionHistory.type.default');
            }
        }
    }
});
