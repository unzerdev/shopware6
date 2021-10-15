import template from './sw-order-list.html.twig';

Shopware.Component.override('sw-order-list', {
    template,

    methods: {
        getOrderColumns() {
            const baseColumns = this.$super('getOrderColumns');

            baseColumns.splice(1, 0, {
                property: 'unzerPaymentTransactionId',
                label: 'unzer-payment.order-list.transactionId',
                allowResize: true
            });

            return baseColumns;
        }
    }
});
