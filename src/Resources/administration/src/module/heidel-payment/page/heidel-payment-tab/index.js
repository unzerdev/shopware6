const { Component, State } = Shopware;
import template from './heidel-payment-tab.html.twig';

Component.register('heidel-payment-tab', {
    template,

    inject: ['HeidelPaymentService'],

    data() {
        return {
            paymentResources: [],
            isLoading: true
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
        createdComponent() {
            this.loadData();
        },

        orderStore() {
            return State.getStore('order');
        },

        resetDataAttributes() {
            this.paymentResources = [];
            this.isLoading = true;
        },

        reloadPaymentDetails() {
            this.resetDataAttributes();
            this.loadData();
        },

        loadData() {
            const orderId = this.$route.params.id;

            this.orderStore().getByIdAsync(orderId).then((order) => {
                this.order = order;

                this.order.getAssociation('transactions').getList().then((orderTransactions) => {
                    orderTransactions.items.forEach((orderTransaction) => {
                        if (!orderTransaction.customFields) {
                            return;
                        }

                        if (!orderTransaction.customFields.heidelpay_is_transaction) {
                            return;
                        }

                        this.HeidelPaymentService.fetchPaymentDetails(orderTransaction.id)
                            .then((response) => {
                                this.isLoading = false;
                                this.paymentResources.push(response);
                            })
                            .catch(() => {
                                this.isLoading = false;
                            });
                    });
                });
            });
        },
    },
});
