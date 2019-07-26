import { Component, Filter, Mixin, State } from 'src/core/shopware';
import template from './heidel-payment-tab.html.twig';
import './heidel-payment-tab.scss';

Component.register('heidel-payment-tab', {
    template,

    inject: ['HeidelPaymentService'],

    data() {
        return {
            paymentResources: [],
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
        orderStore() {
            return State.getStore('order');
        },

        createdComponent() {
            const orderId = this.$route.params.id;

            this.orderStore().getByIdAsync(orderId).then((order) => {
                this.order = order;

                this.order.getAssociation('transactions').getList().then((orderTransactions) => {
                    orderTransactions.items.forEach((orderTransaction) => {
                        this.HeidelPaymentService.fetchPaymentDetails(orderTransaction.id)
                            .then((response) => {
                                this.isLoading = false;
                                this.paymentResources.push(response);
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
