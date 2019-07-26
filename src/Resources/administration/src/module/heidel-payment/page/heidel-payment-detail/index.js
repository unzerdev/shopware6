import { Component, Filter, Mixin, State } from 'src/core/shopware';
import template from './heidel-payment-detail.html.twig';
import './heidel-payment-detail.scss';

Component.register('heidel-payment-detail', {
    template,

    inject: ['HeidelPaymentService'],

    data() {
        return {
            histories: [],
            isLoading: true,
        };
    },

    created() {
        this.createdComponent();
    },

    watch: {
        '$route'() {
            this.resetDataAttributes();
            this.createdComponent();
        }
    },

    methods: {
        fetchPaymentHistory(transaction) {
            let me = this;

            this.HeidelPaymentService.fetchPaymentHistory(transaction)
                .then((response) => {
                    console.log(response);
                })
                .catch((errorResponse) => {
                    console.log(errorResponse);
                });
        },

        orderStore() {
            return State.getStore('order');
        },

        createdComponent() {
            const orderId = this.$route.params.id;

            this.orderStore().getByIdAsync(orderId).then((order) => {
                this.order = order;

                this.order.getAssociation('transactions').getList().then((orderTransactions) => {
                    orderTransactions.items.forEach((orderTransaction) => {
                        this.HeidelPaymentService.fetchPaymentHistory(orderTransaction.id)
                            .then((response) => {
                                this.isLoading = false;

                                this.histories.push(response.history);
                            })
                            .catch((errorResponse) => {
                                console.log(errorResponse);
                            });
                    });
                });
            });
        },
    },

    resetDataAttributes() {
        this.histories = [];
        this.isLoading = true;
    }
});
