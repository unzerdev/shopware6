import { Component } from 'src/core/shopware';
import template from './sw-order.html.twig';
import Criteria from 'src/core/data-new/criteria.data';

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
        },
    },

    created() {
        // ToDo with NEXT-3911: Remove this Quickfix
        this.$router.push({ name: 'sw.order.detail', params: { id: this.orderId } });
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

                orderRepository.get(this.orderId, this.context, orderCriteria).then((order) => {
                    order.transactions.forEach((orderTransaction) => {
                        if (!orderTransaction.customFields) {
                            return;
                        }

                        if (!orderTransaction.customFields.heidelpay_transaction) {
                            return;
                        }

                        this.isHeidelpayPayment = true;
                    });
                });
            },
            immediate: true
        }
    },
});
