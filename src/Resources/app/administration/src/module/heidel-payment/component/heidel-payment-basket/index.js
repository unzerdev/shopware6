import template from './heidel-payment-basket.html.twig';

const { Component } = Shopware;

Component.register('heidel-payment-basket', {
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

            this.paymentResource.basket.basketItems.forEach((basketItem) => {
                const amountGross = this.$options.filters.currency(
                    parseFloat(basketItem.amountGross),
                    this.paymentResource.currency
                );
                const amountNet = this.$options.filters.currency(
                    parseFloat(basketItem.amountNet),
                    this.paymentResource.currency
                );

                data.push({
                    quantity: basketItem.quantity,
                    title: basketItem.title,
                    amountGross: amountGross,
                    amountNet: amountNet
                });
            });

            return data;
        },

        columns: function () {
            return [
                {
                    property: 'quantity',
                    label: this.$tc('heidel-payment.paymentDetails.basket.column.quantity'),
                    rawData: true
                },
                {
                    property: 'title',
                    label: this.$tc('heidel-payment.paymentDetails.basket.column.title'),
                    rawData: true
                },
                {
                    property: 'amountGross',
                    label: this.$tc('heidel-payment.paymentDetails.basket.column.amountGross'),
                    rawData: true
                },
                {
                    property: 'amountNet',
                    label: this.$tc('heidel-payment.paymentDetails.basket.column.amountNet'),
                    rawData: true
                }
            ];
        }
    }
});
