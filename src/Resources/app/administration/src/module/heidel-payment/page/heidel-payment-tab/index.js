const { Component, StateDeprecated } = Shopware;
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
            return StateDeprecated.getStore('order');
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

                this.order.getAssociation('transactions').getList({}).then((orderTransactions) => {
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
                                this.paymentResources.push(this.calculateAmounts(response));
                            })
                            .catch(() => {
                                this.isLoading = false;
                            });
                    });
                });
            });
        },

        calculateAmounts(paymentResource) {
            window.console.log(paymentResource);
            paymentResource.calculatedAmounts = {
                remaining: paymentResource.basket.amountTotalGross,
                charged: 0.00,
                cancelled: 0.00,
            };

            paymentResource.transactions.forEach((transaction) => {
                if (transaction.type === 'cancellation') {
                    paymentResource.calculatedAmounts.cancelled += transaction.amount;
                } else if (transaction.type === 'charge') {
                    paymentResource.calculatedAmounts.charged += transaction.amount;
                    paymentResource.calculatedAmounts.remaining -= transaction.amount;
                }
            });
            window.console.log(paymentResource);
            return paymentResource;
        }
    },
});
