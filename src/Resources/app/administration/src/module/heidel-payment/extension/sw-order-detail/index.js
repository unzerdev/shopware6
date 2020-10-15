import template from './sw-order-detail.html.twig';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isHeidelpayPayment: false
        };
    },

    computed: {
        showTabs() {
            return true; // TODO remove with PT-10455
        },

        paymentMethodStore() {
            return State.getStore('payment_method');
        }
    },

    watch: {
        orderId: {
            deep: true,
            handler() {
                if (!this.orderId) {
                    this.isHeidelpayPayment = false;

                    return;
                }

                const orderRepository = this.repositoryFactory.create('order');
                const orderCriteria = new Criteria(1, 1);
                orderCriteria.addAssociation('transactions');

                orderRepository.get(this.orderId, Context.api, orderCriteria).then((order) => {
                    order.transactions.forEach((orderTransaction) => {
                        if (!orderTransaction.customFields) {
                            return;
                        }

                        if (!orderTransaction.customFields.heidelpay_is_transaction) {
                            return;
                        }

                        this.isHeidelpayPayment = true;
                    });
                });
            },
            immediate: true
        }
    }
});
