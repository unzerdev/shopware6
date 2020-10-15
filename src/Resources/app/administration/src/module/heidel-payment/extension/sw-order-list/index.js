import template from './sw-order-list.html.twig';

Shopware.Component.override('sw-order-list', {
    template,

    computed: {
        orderColumns() {
            return this.getEnhancedOrderColumns();
        }
    },

    methods: {
        getEnhancedOrderColumns() {
            const baseColumns = this.getOrderColumns();

            baseColumns.splice(1, 0, {
                property: 'heidelTransactionId',
                label: 'heidel-payment.order-list.transactionId',
                allowResize: true
            });

            return baseColumns;
        }
    }
});
